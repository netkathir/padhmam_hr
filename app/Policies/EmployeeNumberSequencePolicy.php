<?php

namespace App\Policies;

use App\Models\EmployeeNumberSequence;
use App\Models\User;

class EmployeeNumberSequencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee-number-sequence.view');
    }

    public function view(User $user, EmployeeNumberSequence $sequence): bool
    {
        return $user->hasPermissionTo('employee-number-sequence.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $sequence->rule->branch_id);
    }

    public function adjust(User $user, EmployeeNumberSequence $sequence): bool
    {
        return $user->hasPermissionTo('employee-number-sequence.adjust')
            && ($user->isSuperAdministrator() || $user->branch_id === $sequence->rule->branch_id);
    }
}
