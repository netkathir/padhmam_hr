<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use App\Services\BranchContext;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('section.view');
    }

    public function view(User $user, Section $section): bool
    {
        return $user->hasPermissionTo('section.view')
            && ($user->isSuperAdministrator() || $user->branch_id === $section->branch_id);
    }

    public function create(User $user): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('section.create') && ! $branchContext->isAllBranchesSelected();
    }

    public function update(User $user, Section $section): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('section.edit')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $section->branch_id);
    }

    public function activate(User $user, Section $section): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('section.activate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $section->branch_id);
    }

    public function inactivate(User $user, Section $section): bool
    {
        $branchContext = app(BranchContext::class);

        return $user->hasPermissionTo('section.inactivate')
            && ! $branchContext->isAllBranchesSelected()
            && ($user->isSuperAdministrator() || $user->branch_id === $section->branch_id);
    }
}
