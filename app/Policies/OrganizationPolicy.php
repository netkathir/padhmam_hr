<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('organization.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->hasPermissionTo('organization.view');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermissionTo('organization.edit');
    }
}
