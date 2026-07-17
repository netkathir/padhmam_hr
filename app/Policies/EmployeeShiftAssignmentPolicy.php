<?php

namespace App\Policies;

use App\Models\EmployeeShiftAssignment;
use App\Models\User;
use App\Services\BranchContext;

class EmployeeShiftAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee-shift-assignment.view');
    }

    public function view(User $user, EmployeeShiftAssignment $assignment): bool
    {
        return $user->hasPermissionTo('employee-shift-assignment.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $assignment->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-shift-assignment.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function editScheduled(User $user, EmployeeShiftAssignment $assignment): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-shift-assignment.edit-scheduled')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $assignment->branch_id);
    }

    public function change(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-shift-assignment.change') && ! $branchContext->isAllBranchesSelected();
    }

    public function temporary(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-shift-assignment.temporary') && ! $branchContext->isAllBranchesSelected();
    }

    public function cancel(User $user, EmployeeShiftAssignment $assignment): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-shift-assignment.cancel')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $assignment->branch_id);
    }

    public function viewHistory(User $user): bool
    {
        return $user->hasPermissionTo('employee-shift-assignment.view-history');
    }
}
