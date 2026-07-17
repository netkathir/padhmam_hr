<?php

namespace App\Services\Employees;

use App\Models\Branch;
use App\Models\ContractorBranchEngagement;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EmployeeNumbering\EmployeeNumberGeneratorService;
use App\Services\EmployeeNumbering\EmployeeNumberReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeRegistrationService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeService $employeeService,
        private readonly EmployeeValidationService $validationService,
        private readonly EmployeeShiftValidationService $shiftValidationService,
        private readonly EmployeeContractorValidationService $contractorValidationService,
        private readonly EmployeeDuplicateDetectionService $duplicateDetectionService,
        private readonly EmployeeHistoryService $historyService,
        private readonly EmployeeNumberGeneratorService $numberGenerator,
        private readonly EmployeeNumberReservationService $reservationService,
    ) {
    }

    /**
     * Minimal Draft creation (spec section 36): only Employee Type, First
     * Name, and Date of Birth or Date of Joining are required — everything
     * else the user has already filled in on the registration form is
     * still saved, just without the strict validation Complete Registration
     * applies.
     */
    public function createDraft(array $data, int $branchId, User $actor, Request $request): Employee
    {
        [$data, $related] = $this->extractRelated($data);

        $employeeType = EmployeeType::query()->findOrFail($data['employee_type_id']);
        $data = $this->applyEmployeeTypeDefaults($data, $employeeType);
        $data['display_name'] = Employee::buildDisplayName($data['first_name'] ?? null, $data['middle_name'] ?? null, $data['last_name'] ?? null);

        return DB::transaction(function () use ($data, $related, $branchId, $actor, $request): Employee {
            $employee = Employee::create([
                ...$data,
                'employee_uuid' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'status' => Employee::STATUS_DRAFT,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->employeeService->syncRelatedRecords($employee, ...$related, actor: $actor, request: $request);

            $this->auditService->record('employee_draft_created', 'employee', $employee, [], $employee->fresh()->toArray(), $request);

            return $employee->fresh();
        });
    }

    /**
     * Changing Employee Type while still a Draft recalculates applicability
     * and default Shift Type, and clears now-invalid Contractor/Shift data
     * (spec section 34) — this never happens once registration is final.
     */
    public function updateDraft(Employee $employee, array $data, User $actor, Request $request): Employee
    {
        if (! $employee->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only a Draft Employee can be saved as a draft.',
            ]);
        }

        [$data, $related] = $this->extractRelated($data);

        $newTypeId = isset($data['employee_type_id']) ? (int) $data['employee_type_id'] : $employee->employee_type_id;
        $employeeTypeChanged = $newTypeId !== $employee->employee_type_id;
        $employeeType = EmployeeType::query()->findOrFail($newTypeId);

        if ($employeeTypeChanged) {
            $data['shift_type'] = $employeeType->default_shift_type;
            $data['fixed_shift_id'] = null;
            $data['attendance_applicable'] = $employeeType->attendance_applicable;
            $data['leave_applicable'] = $employeeType->leave_applicable;
            $data['payroll_applicable'] = $employeeType->payroll_applicable;
            $data['overtime_applicable'] = $employeeType->overtime_applicable;

            if (! $employeeType->requiresContractor()) {
                $data['contractor_id'] = null;
                $data['contractor_branch_engagement_id'] = null;
            }
        } else {
            $data = $this->applyEmployeeTypeDefaults($data, $employeeType, $employee);
        }

        $data['display_name'] = Employee::buildDisplayName(
            $data['first_name'] ?? $employee->first_name,
            $data['middle_name'] ?? $employee->middle_name,
            $data['last_name'] ?? $employee->last_name,
        );

        return DB::transaction(function () use ($employee, $data, $related, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();

            $employee->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $employee->save();

            $this->employeeService->syncRelatedRecords($employee, ...$related, actor: $actor, request: $request);

            $this->auditService->record('employee_draft_updated', 'employee', $employee, $old, $employee->fresh()->toArray(), $request);

            return $employee->fresh();
        });
    }

    /**
     * Runs the full sequence from spec section 52. Everything from the
     * capacity-check lock through the Employee Number reservation and
     * finalization happens inside a single outer transaction: Laravel
     * transparently uses savepoints for the nested transactions opened by
     * the Number Generator/Reservation services, so if any later step
     * fails, the whole thing — including the consumed serial — rolls back
     * together. No partially active Employee, no burned number.
     */
    public function completeRegistration(Employee $employee, array $data, User $actor, Request $request): Employee
    {
        try {
            return $this->runCompleteRegistration($employee, $data, $actor, $request);
        } catch (ValidationException $exception) {
            $this->auditService->record(
                'employee_registration_failed',
                'employee',
                $employee,
                [],
                ['errors' => $exception->errors()],
                $request,
            );

            throw $exception;
        }
    }

    private function runCompleteRegistration(Employee $employee, array $data, User $actor, Request $request): Employee
    {
        if (! $employee->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Only a Draft Employee can complete registration.',
            ]);
        }

        [$data, $related] = $this->extractRelated($data);
        [$contact, $addresses, $statutory, $bank, $emergencyContacts] = $related;
        $duplicateAcknowledged = (bool) ($data['duplicate_warning_acknowledged'] ?? false);
        unset($data['duplicate_warning_acknowledged']);

        $branchId = $employee->branch_id;
        $employeeType = EmployeeType::query()->findOrFail($data['employee_type_id']);
        $dateOfJoining = Carbon::parse($data['date_of_joining']);

        $duplicateErrors = $this->duplicateDetectionService->definiteDuplicateErrors(
            ['statutory' => $statutory, 'bank' => $bank],
            $employee->id,
        );

        if ($duplicateErrors !== []) {
            throw ValidationException::withMessages($duplicateErrors);
        }

        $warnings = $this->duplicateDetectionService->warnings([
            'first_name' => $data['first_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'contact' => $contact,
        ], $employee->id);

        if ($warnings !== [] && ! $duplicateAcknowledged) {
            throw ValidationException::withMessages([
                'duplicate_warning_acknowledged' => implode(' ', $warnings).' Acknowledge the warning to continue.',
            ]);
        }

        if (! $employee->branch?->isActive()) {
            throw ValidationException::withMessages(['branch' => 'This Employee cannot be registered because the active Branch is inactive.']);
        }

        if (! $employeeType->isActive()) {
            throw ValidationException::withMessages(['employee_type_id' => 'Select an active Employee Type.']);
        }

        $this->validationService->assertDepartmentSectionDesignation(
            $branchId,
            (int) $data['department_id'],
            $data['section_id'] ?? null,
            (int) $data['designation_id'],
        );

        $this->validationService->assertNoCircularReportingChain($employee->id, $data['reporting_manager_id'] ?? null);

        $engagement = null;

        if ($employeeType->requiresContractor()) {
            $engagement = $this->contractorValidationService->assertValid(
                (int) $data['contractor_id'],
                (int) $data['contractor_branch_engagement_id'],
                $branchId,
                $dateOfJoining,
            );
        }

        $this->shiftValidationService->assertValid(
            $data['shift_type'],
            $data['fixed_shift_id'] ?? null,
            $branchId,
            $employeeType,
            $dateOfJoining,
        );

        return DB::transaction(function () use (
            $employee, $data, $contact, $addresses, $statutory, $bank, $emergencyContacts,
            $employeeType, $dateOfJoining, $branchId, $engagement, $actor, $request,
        ): Employee {
            $locked = Employee::query()->withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->first();

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages(['status' => 'This Employee is no longer a Draft.']);
            }

            if ($engagement) {
                $lockedEngagement = ContractorBranchEngagement::query()->withoutGlobalScopes()->whereKey($engagement->id)->lockForUpdate()->first();
                $this->contractorValidationService->assertCapacityAvailable($lockedEngagement, $locked->id);
            }

            $old = $locked->replicate()->toArray();

            $branch = Branch::query()->findOrFail($branchId);
            $reservation = $this->numberGenerator->reserve($branch, $employeeType, $dateOfJoining, $actor, $request);

            $data['display_name'] = Employee::buildDisplayName($data['first_name'], $data['middle_name'] ?? null, $data['last_name'] ?? null);
            $data['employee_number'] = $reservation->generated_employee_number;
            $data['registration_completed_at'] = now();
            $data['status'] = Employee::STATUS_ACTIVE;
            $data['activated_at'] = now();

            $locked->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $locked->save();

            $this->employeeService->syncRelatedRecords($locked, $contact, $addresses, $statutory, $bank, $emergencyContacts, $actor, $request);

            $this->reservationService->finalize($reservation, $request);

            $new = $locked->fresh()->toArray();

            $this->historyService->record($locked, 'registration_completed', $old, $new, $actor);
            $this->auditService->record('employee_registration_completed', 'employee', $locked, $old, $new, $request);

            return $locked->fresh();
        });
    }

    /**
     * @return array{0: array, 1: array{0: array, 1: array, 2: array, 3: array, 4: array}}
     */
    private function extractRelated(array $data): array
    {
        $related = [
            $data['contact'] ?? [],
            $data['addresses'] ?? [],
            $data['statutory'] ?? [],
            $data['bank'] ?? [],
            $data['emergency_contacts'] ?? [],
        ];

        unset($data['contact'], $data['addresses'], $data['statutory'], $data['bank'], $data['emergency_contacts']);

        return [$data, $related];
    }

    private function applyEmployeeTypeDefaults(array $data, EmployeeType $employeeType, ?Employee $employee = null): array
    {
        $data['shift_type'] ??= $employee->shift_type ?? $employeeType->default_shift_type;
        $data['attendance_applicable'] ??= $employee?->attendance_applicable ?? $employeeType->attendance_applicable;
        $data['leave_applicable'] ??= $employee?->leave_applicable ?? $employeeType->leave_applicable;
        $data['payroll_applicable'] ??= $employee?->payroll_applicable ?? $employeeType->payroll_applicable;
        $data['overtime_applicable'] ??= $employee?->overtime_applicable ?? $employeeType->overtime_applicable;

        return $data;
    }
}
