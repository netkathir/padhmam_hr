<?php

namespace App\Policies;

use App\Models\Contractor;
use App\Models\User;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('contractor.view');
    }

    public function view(User $user, Contractor $contractor): bool
    {
        if (! $user->hasPermissionTo('contractor.view')) {
            return false;
        }

        if ($user->isSuperAdministrator() || ! $user->hasRole('branch-administrator')) {
            return true;
        }

        return $contractor->branchEngagements()->where('branch_id', $user->branch_id)->exists();
    }

    /**
     * Contractor creation is organization-level; it does not depend on the
     * active branch context, only on being an authorized company-level user.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('contractor.create');
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $user->hasPermissionTo('contractor.edit');
    }

    public function activate(User $user, Contractor $contractor): bool
    {
        return $user->hasPermissionTo('contractor.activate');
    }

    public function inactivate(User $user, Contractor $contractor): bool
    {
        return $user->hasPermissionTo('contractor.inactivate');
    }
}
