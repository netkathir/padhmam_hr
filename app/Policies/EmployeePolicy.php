<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\BranchContext;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employee.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function completeRegistration(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.complete-registration')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function activate(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function inactivate(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function reactivate(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.reactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function separate(User $user, Employee $employee): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee.separate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    public function viewSensitive(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employee.view-sensitive')
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }

    /**
     * Nullable Employee so this can also gate the sensitive fields on the
     * Create Registration form, where no Employee exists yet.
     */
    public function editSensitive(User $user, ?Employee $employee = null): bool
    {
        $branchContext = app(BranchContext::class);

        if (! $user->hasPermissionTo('employee.edit-sensitive') || $branchContext->isAllBranchesSelected()) {
            return false;
        }

        if (! $employee) {
            return true;
        }

        return $user->isSuperAdministrator() || $user->branch_id === $employee->branch_id;
    }

    public function viewHistory(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employee.view-history')
            && ($user->isSuperAdministrator() || $user->branch_id === $employee->branch_id);
    }
}
