<?php

namespace App\Services\EmployeeShifts;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeShiftAssignmentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeShiftOverlapService $overlapService,
        private readonly EmployeeShiftResolutionService $resolutionService,
    ) {
    }

    /**
     * Employee eligibility (spec section 7) — checked before every kind of
     * assignment (initial, manual rotational, change, temporary).
     */
    public function assertEmployeeEligible(Employee $employee): void
    {
        if (! $employee->isActive()) {
            throw ValidationException::withMessages([
                'employee' => 'Only an Active Employee may receive a Shift assignment.',
            ]);
        }

        if (! $employee->hasCompletedRegistration()) {
            throw ValidationException::withMessages([
                'employee' => 'This Employee has not completed registration.',
            ]);
        }

        if (! $employee->employeeType?->isActive()) {
            throw ValidationException::withMessages([
                'employee' => 'This Employee\'s Employee Type is inactive.',
            ]);
        }
    }

    /**
     * Shift/Employee compatibility (spec sections 7–9). $assignmentType
     * governs whether the Shift's own classification must match Fixed or
     * Rotational; the Employee's configured shift_type is checked
     * separately by the caller for the specific action being performed.
     */
    public function assertShiftCompatible(
        Employee $employee,
        Shift $shift,
        string $assignmentType,
        Carbon $effectiveFrom,
        ?Carbon $effectiveTo,
    ): void {
        if ($shift->branch_id !== $employee->branch_id) {
            throw ValidationException::withMessages([
                'shift_id' => 'The selected Shift does not belong to the active Branch.',
            ]);
        }

        if (! $shift->isActive()) {
            throw ValidationException::withMessages([
                'shift_id' => 'The selected Shift is not Active.',
            ]);
        }

        if (! $shift->isEffectiveOn($effectiveFrom) || ($effectiveTo && ! $shift->isEffectiveOn($effectiveTo))) {
            throw ValidationException::withMessages([
                'shift_id' => 'The selected Shift is not effective throughout the assignment period.',
            ]);
        }

        if (! $shift->supportsEmployeeType($employee->employeeType)) {
            throw ValidationException::withMessages([
                'shift_id' => 'The selected Shift does not support this Employee\'s Employee Type.',
            ]);
        }

        // A Temporary assignment may point at either a Fixed or a Rotational
        // Shift record — assignment_type "temporary" describes the override
        // itself, not the underlying Shift's own classification. Only a
        // regular (Fixed/Rotational) assignment must match the Shift's
        // classification to the assignment type being created.
        if ($assignmentType === EmployeeShiftAssignment::TYPE_TEMPORARY) {
            return;
        }

        $expectedShiftType = $assignmentType === EmployeeShiftAssignment::TYPE_ROTATIONAL
            ? Shift::TYPE_ROTATIONAL
            : Shift::TYPE_FIXED;

        if ($shift->shift_type !== $expectedShiftType) {
            throw ValidationException::withMessages([
                'shift_id' => "The selected Shift must be a {$expectedShiftType} Shift for this assignment type.",
            ]);
        }
    }

    /**
     * Creates a new Fixed or Rotational assignment for an Employee with no
     * current applicable Shift (initial assignment, or manual Rotational
     * assignment for a "Shift Assignment Pending" Employee). The
     * assignment_type is derived from the Employee's own shift_type
     * (spec sections 6–9): a Fixed Employee may only receive a Fixed
     * assignment, a Rotational Employee only a Rotational one.
     */
    public function assign(Employee $employee, array $data, User $actor, Request $request): EmployeeShiftAssignment
    {
        $this->assertEmployeeEligible($employee);

        $assignmentType = $employee->usesFixedShift() ? EmployeeShiftAssignment::TYPE_FIXED : EmployeeShiftAssignment::TYPE_ROTATIONAL;

        $shift = Shift::query()->withoutGlobalScopes()->with('employeeTypes')->findOrFail($data['shift_id']);
        $effectiveFrom = Carbon::parse($data['effective_from']);
        $effectiveTo = isset($data['effective_to']) ? Carbon::parse($data['effective_to']) : null;

        $this->assertShiftCompatible($employee, $shift, $assignmentType, $effectiveFrom, $effectiveTo);

        return DB::transaction(function () use ($employee, $shift, $assignmentType, $effectiveFrom, $effectiveTo, $data, $actor, $request): EmployeeShiftAssignment {
            // Row-lock the Employee and re-check for overlaps under lock
            // (spec section 33) so two concurrent assignment requests for
            // the same Employee cannot both succeed.
            Employee::query()->withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();
            EmployeeShiftAssignment::query()->withoutGlobalScopes()->where('employee_id', $employee->id)->lockForUpdate()->get();

            $this->overlapService->assertNoOverlap($employee, $assignmentType, $effectiveFrom, $effectiveTo);

            $status = (new EmployeeShiftAssignment(['effective_from' => $effectiveFrom, 'effective_to' => $effectiveTo]))->computeStatus();

            $assignment = EmployeeShiftAssignment::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'assignment_type' => $assignmentType,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_current' => $status === EmployeeShiftAssignment::STATUS_ACTIVE,
                'assignment_reason' => $data['assignment_reason'] ?? null,
                'status' => $status,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncEmployeeFixedShiftCache($employee, $assignmentType, $shift, $status);

            $this->auditService->record(
                $assignmentType === EmployeeShiftAssignment::TYPE_FIXED ? 'employee_initial_shift_assigned' : 'employee_rotational_shift_assigned',
                'employee_shift_assignment',
                $assignment,
                [],
                $assignment->fresh()->toArray(),
                $request,
            );

            return $assignment->fresh();
        });
    }

    /**
     * Called from within Employee Registration's own transaction (spec
     * section 13) so the Employee record and its initial Shift assignment
     * are created atomically. Does not open its own transaction — the
     * caller (EmployeeRegistrationService) already holds one.
     */
    public function createInitialFixedAssignment(Employee $employee, Shift $shift, Carbon $effectiveFrom, User $actor, Request $request): EmployeeShiftAssignment
    {
        $status = (new EmployeeShiftAssignment(['effective_from' => $effectiveFrom, 'effective_to' => null]))->computeStatus();

        $assignment = EmployeeShiftAssignment::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'assignment_type' => EmployeeShiftAssignment::TYPE_FIXED,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'is_current' => $status === EmployeeShiftAssignment::STATUS_ACTIVE,
            'assignment_reason' => 'Initial Fixed Shift assignment during Employee Registration.',
            'status' => $status,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->auditService->record('employee_initial_shift_assigned', 'employee_shift_assignment', $assignment, [], $assignment->fresh()->toArray(), $request);

        return $assignment;
    }

    private function syncEmployeeFixedShiftCache(Employee $employee, string $assignmentType, Shift $shift, string $status): void
    {
        if ($assignmentType === EmployeeShiftAssignment::TYPE_FIXED && $status === EmployeeShiftAssignment::STATUS_ACTIVE) {
            $employee->update(['fixed_shift_id' => $shift->id]);
        }
    }
}
