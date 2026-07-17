<?php

namespace App\Services\Employees;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Section;
use Illuminate\Validation\ValidationException;

class EmployeeValidationService
{
    public function assertDepartmentSectionDesignation(int $branchId, int $departmentId, ?int $sectionId, int $designationId): void
    {
        $department = Department::query()->withoutGlobalScopes()->find($departmentId);

        if (! $department || $department->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'department_id' => 'Select a Department that belongs to the active Branch.',
            ]);
        }

        if ($sectionId) {
            $section = Section::query()->withoutGlobalScopes()->find($sectionId);

            if (! $section || $section->branch_id !== $branchId || $section->department_id !== $departmentId) {
                throw ValidationException::withMessages([
                    'section_id' => 'Select a Section that belongs to the selected Department.',
                ]);
            }
        }

        $designation = Designation::query()->withoutGlobalScopes()->find($designationId);

        if (! $designation || $designation->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'designation_id' => 'Select a Designation that belongs to the active Branch.',
            ]);
        }

        $level = $designation->scopeLevel();

        if ($level === Designation::SCOPE_DEPARTMENT && $designation->department_id !== $departmentId) {
            throw ValidationException::withMessages([
                'designation_id' => 'The selected Designation belongs to a different Department.',
            ]);
        }

        if ($level === Designation::SCOPE_SECTION
            && ($designation->department_id !== $departmentId || $designation->section_id !== $sectionId)) {
            throw ValidationException::withMessages([
                'designation_id' => 'The selected Designation belongs to a different Department or Section.',
            ]);
        }
    }

    /**
     * Walks the candidate manager's existing reporting chain to detect both
     * direct and indirect circular relationships (spec section 18). Bounded
     * by a configurable max depth purely as a defensive guard against a
     * pre-existing data error creating an infinite loop.
     */
    public function assertNoCircularReportingChain(?int $employeeId, ?int $newManagerId): void
    {
        if (! $newManagerId) {
            return;
        }

        if ($employeeId && $newManagerId === $employeeId) {
            throw ValidationException::withMessages([
                'reporting_manager_id' => 'An Employee cannot report to themselves.',
            ]);
        }

        $currentId = $newManagerId;
        $depth = 0;
        $maxDepth = (int) config('hrms.employee_reporting_chain_max_depth', 50);

        while ($currentId && $depth < $maxDepth) {
            if ($employeeId && $currentId === $employeeId) {
                throw ValidationException::withMessages([
                    'reporting_manager_id' => 'This assignment would create a circular reporting chain.',
                ]);
            }

            $currentId = Employee::query()->withoutGlobalScopes()->whereKey($currentId)->value('reporting_manager_id');
            $depth++;
        }
    }

    /**
     * Activation-readiness checks shared by final registration and the
     * explicit Activate action (spec section 40).
     */
    public function assertActivatable(Employee $employee): void
    {
        $errors = [];

        if (! $employee->branch?->isActive()) {
            $errors['branch'] = 'This Employee cannot be activated because its Branch is inactive.';
        }

        if (! $employee->employeeType?->isActive()) {
            $errors['employee_type_id'] = 'This Employee cannot be activated because its Employee Type is inactive.';
        }

        if (! $employee->department_id || ! $employee->department?->isActive()) {
            $errors['department_id'] = 'This Employee cannot be activated because its Department is missing or inactive.';
        }

        if ($employee->section_id && ! $employee->section?->isActive()) {
            $errors['section_id'] = 'This Employee cannot be activated because its Section is inactive.';
        }

        if (! $employee->designation_id || ! $employee->designation?->isActive()) {
            $errors['designation_id'] = 'This Employee cannot be activated because its Designation is missing or inactive.';
        }

        if (! $employee->first_name || ! $employee->date_of_birth || ! $employee->date_of_joining || ! $employee->gender) {
            $errors['first_name'] = 'Complete the mandatory Personal and Employment details before activation.';
        }

        if ($employee->reporting_manager_id) {
            $manager = Employee::query()->withoutGlobalScopes()->find($employee->reporting_manager_id);

            if (! $manager || ! $manager->isActive() || $manager->branch_id !== $employee->branch_id) {
                $errors['reporting_manager_id'] = 'The assigned Reporting Manager is no longer a valid active Employee in this Branch.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
