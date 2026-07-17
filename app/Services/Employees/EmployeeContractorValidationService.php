<?php

namespace App\Services\Employees;

use App\Models\ContractorBranchEngagement;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmployeeContractorValidationService
{
    public function assertValid(int $contractorId, int $engagementId, int $branchId, Carbon $dateOfJoining): ContractorBranchEngagement
    {
        $engagement = ContractorBranchEngagement::query()
            ->withoutGlobalScopes()
            ->with(['contractor', 'branch'])
            ->find($engagementId);

        if (! $engagement || $engagement->contractor_id !== $contractorId) {
            throw ValidationException::withMessages([
                'contractor_branch_engagement_id' => 'The selected Branch Engagement does not match the selected Contractor.',
            ]);
        }

        if ($engagement->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'contractor_branch_engagement_id' => 'The selected Branch Engagement does not belong to the active Branch.',
            ]);
        }

        if (! $engagement->isValidForEmployeeAssignment($dateOfJoining)) {
            throw ValidationException::withMessages([
                'contractor_branch_engagement_id' => 'The selected Contractor Engagement is not valid for the Date of Joining (inactive Contractor or Branch, contract period, or labour licence expiry).',
            ]);
        }

        return $engagement;
    }

    /**
     * Must be called from within the transaction that finalizes the
     * Employee, after locking the engagement row, so concurrent
     * registrations against the same engagement cannot both pass the
     * capacity check (spec section 21).
     */
    public function assertCapacityAvailable(ContractorBranchEngagement $lockedEngagement, ?int $excludeEmployeeId = null): void
    {
        if (! $lockedEngagement->maximum_labour_count) {
            return;
        }

        $activeCount = Employee::query()
            ->withoutGlobalScopes()
            ->where('contractor_branch_engagement_id', $lockedEngagement->id)
            ->where('status', Employee::STATUS_ACTIVE)
            ->when($excludeEmployeeId, fn ($q) => $q->where('id', '!=', $excludeEmployeeId))
            ->count();

        if ($activeCount >= $lockedEngagement->maximum_labour_count) {
            throw ValidationException::withMessages([
                'contractor_branch_engagement_id' => 'The maximum permitted labour count for this Contractor engagement has been reached.',
            ]);
        }
    }
}
