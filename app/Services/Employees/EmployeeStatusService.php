<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeSeparation;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeStatusService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeValidationService $validationService,
        private readonly EmployeeContractorValidationService $contractorValidationService,
        private readonly EmployeeShiftValidationService $shiftValidationService,
        private readonly EmployeeHistoryService $historyService,
    ) {
    }

    public function activate(Employee $employee, User $actor, Request $request): Employee
    {
        try {
            $this->validationService->assertActivatable($employee);
        } catch (ValidationException $exception) {
            $this->auditService->record('employee_status_change_blocked', 'employee', $employee, [], ['action' => 'activate', 'errors' => $exception->errors()], $request);

            throw $exception;
        }

        return DB::transaction(function () use ($employee, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();

            $employee->update([
                'status' => Employee::STATUS_ACTIVE,
                'activated_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->historyService->record($employee, 'status', ['status' => $old['status']], ['status' => Employee::STATUS_ACTIVE], $actor);
            $this->auditService->record('employee_activated', 'employee', $employee, $old, $employee->fresh()->toArray(), $request);

            return $employee->fresh();
        });
    }

    /**
     * Historical details, Department, Designation, Shift, and Contractor
     * assignments are intentionally left untouched — future Attendance and
     * Payroll modules are responsible for adding their own dependency
     * checks before this action can be exercised against employees they
     * track (spec section 41).
     */
    public function inactivate(Employee $employee, string $effectiveDate, string $reason, User $actor, Request $request): Employee
    {
        return DB::transaction(function () use ($employee, $effectiveDate, $reason, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();

            $employee->update([
                'status' => Employee::STATUS_INACTIVE,
                'inactivated_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->historyService->record(
                $employee,
                'status',
                ['status' => $old['status']],
                ['status' => Employee::STATUS_INACTIVE],
                $actor,
                $reason,
                $effectiveDate,
            );

            $this->auditService->record(
                'employee_inactivated',
                'employee',
                $employee,
                $old,
                [...$employee->fresh()->toArray(), 'reason' => $reason, 'effective_date' => $effectiveDate],
                $request,
            );

            return $employee->fresh();
        });
    }

    public function reactivate(Employee $employee, string $reason, User $actor, Request $request): Employee
    {
        try {
            if ($employee->isSeparated()) {
                throw ValidationException::withMessages([
                    'status' => 'A Separated Employee cannot be reactivated through this action. Rejoining is a separate future process.',
                ]);
            }

            $this->validationService->assertActivatable($employee);

            if ($employee->isContractLabour() && $employee->contractor_branch_engagement_id) {
                $engagement = $employee->contractorBranchEngagement;

                if (! $engagement || ! $engagement->isValidForEmployeeAssignment(now())) {
                    throw ValidationException::withMessages([
                        'contractor_branch_engagement_id' => 'The Contractor Engagement is no longer valid, so this Employee cannot be reactivated.',
                    ]);
                }
            }

            if ($employee->usesFixedShift() && $employee->fixed_shift_id) {
                $shift = $employee->fixedShift;

                if (! $shift || ! $shift->isActive()) {
                    throw ValidationException::withMessages([
                        'fixed_shift_id' => 'The assigned Fixed Shift is no longer active, so this Employee cannot be reactivated.',
                    ]);
                }
            }
        } catch (ValidationException $exception) {
            $this->auditService->record('employee_status_change_blocked', 'employee', $employee, [], ['action' => 'reactivate', 'errors' => $exception->errors()], $request);

            throw $exception;
        }

        return DB::transaction(function () use ($employee, $reason, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();

            $employee->update([
                'status' => Employee::STATUS_ACTIVE,
                'activated_at' => now(),
                'inactivated_at' => null,
                'updated_by' => $actor->id,
            ]);

            $this->historyService->record($employee, 'status', ['status' => $old['status']], ['status' => Employee::STATUS_ACTIVE], $actor, $reason);
            $this->auditService->record('employee_reactivated', 'employee', $employee, $old, [...$employee->fresh()->toArray(), 'reason' => $reason], $request);

            return $employee->fresh();
        });
    }

    public function separate(Employee $employee, array $data, User $actor, Request $request): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();

            $separation = EmployeeSeparation::create([
                ...$data,
                'employee_id' => $employee->id,
                'processed_by' => $actor->id,
            ]);

            $employee->update([
                'status' => Employee::STATUS_SEPARATED,
                'separated_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->historyService->record(
                $employee,
                'status',
                ['status' => $old['status']],
                ['status' => Employee::STATUS_SEPARATED, 'separation_type' => $separation->separation_type],
                $actor,
                $data['separation_reason'] ?? null,
                $data['last_working_date'] ?? null,
            );

            $this->auditService->record(
                'employee_separated',
                'employee',
                $employee,
                $old,
                [...$employee->fresh()->toArray(), 'separation_type' => $separation->separation_type],
                $request,
            );

            return $employee->fresh();
        });
    }
}
