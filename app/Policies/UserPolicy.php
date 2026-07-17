<?php

namespace App\Policies;

use App\Models\User;
use App\Services\BranchContext;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('user.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('user.view') && ($user->isSuperAdministrator() || $user->branch_id === $model->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('user.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, User $model): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('user.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $model->branch_id);
    }
}
