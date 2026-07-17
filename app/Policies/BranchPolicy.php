<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branch.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branch.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $branch->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('branch.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branch.edit')
            && ($user->isSuperAdministrator() || $user->branch_id === $branch->id);
    }

    public function activate(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branch.activate')
            && ($user->isSuperAdministrator() || $user->branch_id === $branch->id);
    }

    public function inactivate(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branch.inactivate')
            && ($user->isSuperAdministrator() || $user->branch_id === $branch->id);
    }

    public function makeHeadOffice(User $user, Branch $branch): bool
    {
        return $user->isSuperAdministrator() && $user->hasPermissionTo('branch.make-head-office');
    }
}
