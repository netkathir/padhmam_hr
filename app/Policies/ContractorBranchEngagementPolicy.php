<?php

namespace App\Policies;

use App\Models\ContractorBranchEngagement;
use App\Models\User;
use App\Services\BranchContext;

class ContractorBranchEngagementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('contractor-engagement.view');
    }

    public function view(User $user, ContractorBranchEngagement $engagement): bool
    {
        return $user->hasPermissionTo('contractor-engagement.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $engagement->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('contractor-engagement.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, ContractorBranchEngagement $engagement): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('contractor-engagement.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $engagement->branch_id);
    }

    public function activate(User $user, ContractorBranchEngagement $engagement): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('contractor-engagement.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $engagement->branch_id);
    }

    public function inactivate(User $user, ContractorBranchEngagement $engagement): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('contractor-engagement.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $engagement->branch_id);
    }
}
