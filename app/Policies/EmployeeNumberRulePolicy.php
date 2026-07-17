<?php

namespace App\Policies;

use App\Models\EmployeeNumberRule;
use App\Models\User;
use App\Services\BranchContext;

class EmployeeNumberRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee-number-rule.view');
    }

    public function view(User $user, EmployeeNumberRule $rule): bool
    {
        return $user->hasPermissionTo('employee-number-rule.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $rule->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-number-rule.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, EmployeeNumberRule $rule): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-number-rule.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $rule->branch_id);
    }

    public function activate(User $user, EmployeeNumberRule $rule): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-number-rule.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $rule->branch_id);
    }

    public function inactivate(User $user, EmployeeNumberRule $rule): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-number-rule.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $rule->branch_id);
    }

    /**
     * Governs both the dynamic create-form preview (no persisted rule yet)
     * and the read-only preview of an existing rule.
     */
    public function preview(User $user, ?EmployeeNumberRule $rule = null): bool
    {
        $branchContext = app(BranchContext::class);

        if (! $user->hasPermissionTo('employee-number-rule.preview') || $branchContext->isAllBranchesSelected()) {
            return false;
        }

        if (! $rule) {
            return true;
        }

        return $user->isSuperAdministrator() || $user->branch_id === $rule->branch_id;
    }

    public function createVersion(User $user, EmployeeNumberRule $rule): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('employee-number-rule.create')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $rule->branch_id);
    }
}
