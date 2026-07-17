<?php

namespace App\Policies;

use App\Models\EmployeeType;
use App\Models\User;

class EmployeeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee-type.view');
    }

    public function view(User $user, EmployeeType $employeeType): bool
    {
        return $user->hasPermissionTo('employee-type.view');
    }

    public function update(User $user, EmployeeType $employeeType): bool
    {
        return $user->hasPermissionTo('employee-type.edit');
    }

    /**
     * Custom Employee Type creation is disabled for the current business
     * requirement (only the three seeded system classifications exist).
     * This remains gated behind a feature flag that defaults to disabled,
     * with no create route wired to it, so this method exists purely as a
     * defensive default should a future route ever call Gate::authorize.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employee-type.create')
            && (bool) config('hrms.features.custom_employee_types', false);
    }
}
