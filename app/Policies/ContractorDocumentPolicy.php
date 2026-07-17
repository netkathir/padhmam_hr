<?php

namespace App\Policies;

use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Models\ContractorDocument;
use App\Models\User;

class ContractorDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('contractor-document.view');
    }

    public function view(User $user, ContractorDocument $document): bool
    {
        return $user->hasPermissionTo('contractor-document.view') && $this->authorizedForContractor($user, $document->contractor, $document->engagement);
    }

    public function upload(User $user, Contractor $contractor, ?ContractorBranchEngagement $engagement = null): bool
    {
        return $user->hasPermissionTo('contractor-document.upload') && $this->authorizedForContractor($user, $contractor, $engagement);
    }

    public function inactivate(User $user, ContractorDocument $document): bool
    {
        return $user->hasPermissionTo('contractor-document.inactivate') && $this->authorizedForContractor($user, $document->contractor, $document->engagement);
    }

    /**
     * A document tied to a specific Branch Engagement is restricted to that
     * Branch. A document tied only to the Contractor profile (no engagement)
     * follows the same organization-wide vs Branch Administrator distinction
     * used by ContractorPolicy::view().
     */
    private function authorizedForContractor(User $user, ?Contractor $contractor, ?ContractorBranchEngagement $engagement): bool
    {
        if ($user->isSuperAdministrator()) {
            return true;
        }

        if ($engagement) {
            return $user->branch_id === $engagement->branch_id;
        }

        if (! $user->hasRole('branch-administrator')) {
            return true;
        }

        return (bool) $contractor?->branchEngagements()->where('branch_id', $user->branch_id)->exists();
    }
}
