<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Services\BranchContext;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('department.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('department.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $department->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('department.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, Department $department): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('department.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $department->branch_id);
    }

    public function activate(User $user, Department $department): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('department.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $department->branch_id);
    }

    public function inactivate(User $user, Department $department): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('department.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $department->branch_id);
    }
}
