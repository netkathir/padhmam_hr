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

class EmployeeShiftChangeService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeShiftOverlapService $overlapService,
        private readonly EmployeeShiftAssignmentService $assignmentService,
    ) {
    }

    /**
     * Fixed Shift Change (spec section 15): the Employee's current Fixed
     * assignment is ended one day before the new one starts (never edited
     * in place, preserving history), a new Fixed assignment is created,
     * and employees.fixed_shift_id is re-synced.
     */
    public function changeFixedShift(Employee $employee, array $data, User $actor, Request $request): EmployeeShiftAssignment
    {
        $this->assignmentService->assertEmployeeEligible($employee);

        if (! $employee->usesFixedShift()) {
            throw ValidationException::withMessages([
                'employee' => 'Only an Employee configured for Fixed Shift may use Shift Change.',
            ]);
        }

        $newShift = Shift::query()->withoutGlobalScopes()->with('employeeTypes')->findOrFail($data['shift_id']);
        $newEffectiveFrom = Carbon::parse($data['effective_from']);

        $this->assignmentService->assertShiftCompatible($employee, $newShift, EmployeeShiftAssignment::TYPE_FIXED, $newEffectiveFrom, null);

        return DB::transaction(function () use ($employee, $newShift, $newEffectiveFrom, $data, $actor, $request): EmployeeShiftAssignment {
            Employee::query()->withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();

            $current = EmployeeShiftAssignment::query()
                ->withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->fixed()
                ->whereIn('status', [EmployeeShiftAssignment::STATUS_SCHEDULED, EmployeeShiftAssignment::STATUS_ACTIVE])
                ->lockForUpdate()
                ->orderByDesc('effective_from')
                ->first();

            if (! $current) {
                throw ValidationException::withMessages([
                    'employee' => 'This Employee has no current Fixed Shift assignment to change. Use Assign Shift instead.',
                ]);
            }

            if ($newEffectiveFrom->lte($current->effective_from)) {
                throw ValidationException::withMessages([
                    'effective_from' => 'The new effective-from date must be after the current assignment\'s effective-from date.',
                ]);
            }

            $newEffectiveTo = $newEffectiveFrom->copy()->subDay();

            $this->overlapService->assertNoOverlap(
                $employee,
                EmployeeShiftAssignment::TYPE_FIXED,
                $newEffectiveFrom,
                null,
                excludeAssignmentId: $current->id,
            );

            $oldValues = $current->toArray();

            // Truncate the old assignment's date range but let its status be
            // recomputed from that new range rather than hardcoding
            // Completed — if the new assignment is only Scheduled (a future
            // effective_from), the old one remains Active/current until the
            // new one actually takes effect.
            $current->effective_to = $newEffectiveTo;
            $oldNewStatus = $current->computeStatus();

            $current->update([
                'effective_to' => $newEffectiveTo,
                'is_current' => $oldNewStatus === EmployeeShiftAssignment::STATUS_ACTIVE,
                'status' => $oldNewStatus,
                'updated_by' => $actor->id,
            ]);

            $status = (new EmployeeShiftAssignment(['effective_from' => $newEffectiveFrom, 'effective_to' => null]))->computeStatus();

            $new = EmployeeShiftAssignment::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'shift_id' => $newShift->id,
                'assignment_type' => EmployeeShiftAssignment::TYPE_FIXED,
                'effective_from' => $newEffectiveFrom,
                'effective_to' => null,
                'is_current' => $status === EmployeeShiftAssignment::STATUS_ACTIVE,
                'assignment_reason' => $data['reason'],
                'change_reference' => (string) $current->id,
                'status' => $status,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if ($status === EmployeeShiftAssignment::STATUS_ACTIVE) {
                $employee->update(['fixed_shift_id' => $newShift->id]);
            }

            $this->auditService->record(
                'employee_fixed_shift_changed',
                'employee_shift_assignment',
                $new,
                ['previous_assignment_id' => $current->id, 'previous_shift_id' => $oldValues['shift_id']],
                $new->fresh()->toArray(),
                $request,
            );

            return $new->fresh();
        });
    }

    /**
     * Temporary Shift assignment (spec sections 16–17): always has a
     * mandatory end date, may overlap an existing Fixed/Rotational
     * assignment (never another Temporary one), and takes resolution
     * priority over both while effective — no change_reference or ending
     * of the underlying regular assignment is involved.
     */
    public function assignTemporary(Employee $employee, array $data, User $actor, Request $request): EmployeeShiftAssignment
    {
        $this->assignmentService->assertEmployeeEligible($employee);

        $shift = Shift::query()->withoutGlobalScopes()->with('employeeTypes')->findOrFail($data['shift_id']);
        $effectiveFrom = Carbon::parse($data['effective_from']);
        $effectiveTo = Carbon::parse($data['effective_to']);

        $this->assignmentService->assertShiftCompatible($employee, $shift, EmployeeShiftAssignment::TYPE_TEMPORARY, $effectiveFrom, $effectiveTo);

        return DB::transaction(function () use ($employee, $shift, $effectiveFrom, $effectiveTo, $data, $actor, $request): EmployeeShiftAssignment {
            Employee::query()->withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();
            EmployeeShiftAssignment::query()->withoutGlobalScopes()->where('employee_id', $employee->id)->lockForUpdate()->get();

            $this->overlapService->assertNoOverlap($employee, EmployeeShiftAssignment::TYPE_TEMPORARY, $effectiveFrom, $effectiveTo);

            $status = (new EmployeeShiftAssignment(['effective_from' => $effectiveFrom, 'effective_to' => $effectiveTo]))->computeStatus();

            $assignment = EmployeeShiftAssignment::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'assignment_type' => EmployeeShiftAssignment::TYPE_TEMPORARY,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_current' => $status === EmployeeShiftAssignment::STATUS_ACTIVE,
                'assignment_reason' => $data['reason'],
                'status' => $status,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'employee_temporary_shift_assigned',
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
     * Editing a Scheduled (future, not-yet-started) assignment in place —
     * the one case where history does not need to be preserved because
     * the assignment has never taken effect (spec section 19).
     */
    public function updateScheduled(EmployeeShiftAssignment $assignment, array $data, User $actor, Request $request): EmployeeShiftAssignment
    {
        if (! $assignment->isScheduled()) {
            throw ValidationException::withMessages([
                'assignment' => 'Only a Scheduled assignment may be edited directly.',
            ]);
        }

        $employee = $assignment->employee;
        $shift = Shift::query()->withoutGlobalScopes()->with('employeeTypes')->findOrFail($data['shift_id']);
        $effectiveFrom = Carbon::parse($data['effective_from']);
        $effectiveTo = isset($data['effective_to']) && $data['effective_to'] ? Carbon::parse($data['effective_to']) : null;

        $this->assignmentService->assertShiftCompatible($employee, $shift, $assignment->assignment_type, $effectiveFrom, $effectiveTo);

        return DB::transaction(function () use ($assignment, $employee, $shift, $effectiveFrom, $effectiveTo, $data, $actor, $request): EmployeeShiftAssignment {
            EmployeeShiftAssignment::query()->withoutGlobalScopes()->where('employee_id', $employee->id)->lockForUpdate()->get();

            $this->overlapService->assertNoOverlap(
                $employee,
                $assignment->assignment_type,
                $effectiveFrom,
                $effectiveTo,
                excludeAssignmentId: $assignment->id,
            );

            $oldValues = $assignment->toArray();

            $status = (new EmployeeShiftAssignment(['effective_from' => $effectiveFrom, 'effective_to' => $effectiveTo]))->computeStatus();

            $assignment->update([
                'shift_id' => $shift->id,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_current' => $status === EmployeeShiftAssignment::STATUS_ACTIVE,
                'assignment_reason' => $data['assignment_reason'] ?? $assignment->assignment_reason,
                'status' => $status,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'employee_scheduled_shift_updated',
                'employee_shift_assignment',
                $assignment,
                $oldValues,
                $assignment->fresh()->toArray(),
                $request,
            );

            return $assignment->fresh();
        });
    }

    /**
     * Cancellation (spec section 20) — allowed only for Scheduled or Active
     * assignments; Cancelled is a terminal status never recalculated by
     * computeStatus(), and is_current is always cleared.
     */
    public function cancel(EmployeeShiftAssignment $assignment, string $reason, User $actor, Request $request): EmployeeShiftAssignment
    {
        if (! in_array($assignment->status, [EmployeeShiftAssignment::STATUS_SCHEDULED, EmployeeShiftAssignment::STATUS_ACTIVE], true)) {
            throw ValidationException::withMessages([
                'assignment' => 'Only a Scheduled or Active assignment may be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $reason, $actor, $request): EmployeeShiftAssignment {
            $oldValues = $assignment->toArray();

            $assignment->update([
                'status' => EmployeeShiftAssignment::STATUS_CANCELLED,
                'is_current' => false,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'employee_shift_assignment_cancelled',
                'employee_shift_assignment',
                $assignment,
                $oldValues,
                $assignment->fresh()->toArray(),
                $request,
            );

            return $assignment->fresh();
        });
    }
}
