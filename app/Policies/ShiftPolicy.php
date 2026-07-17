<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use App\Services\BranchContext;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('shift.view');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->hasPermissionTo('shift.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $shift->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('shift.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, Shift $shift): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('shift.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $shift->branch_id);
    }

    public function activate(User $user, Shift $shift): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('shift.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $shift->branch_id);
    }

    public function inactivate(User $user, Shift $shift): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('shift.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $shift->branch_id);
    }

    public function clone(User $user, Shift $shift): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('shift.clone')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $shift->branch_id);
    }
}
