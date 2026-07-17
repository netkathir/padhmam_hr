<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;
use App\Services\BranchContext;

class DesignationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('designation.view');
    }

    public function view(User $user, Designation $designation): bool
    {
        return $user->hasPermissionTo('designation.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $designation->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('designation.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, Designation $designation): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('designation.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $designation->branch_id);
    }

    public function activate(User $user, Designation $designation): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('designation.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $designation->branch_id);
    }

    public function inactivate(User $user, Designation $designation): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('designation.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $designation->branch_id);
    }
}
