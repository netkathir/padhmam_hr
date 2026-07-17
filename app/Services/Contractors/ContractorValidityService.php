<?php

namespace App\Services\Contractors;

use App\Models\ContractorBranchEngagement;
use Illuminate\Validation\ValidationException;

/**
 * Centralizes the contract-period and licence validity rules so they are not
 * duplicated across controllers, services, and views. Employee capacity
 * checks against maximum_labour_count will be added once the Employee
 * Registration module exists and active labour counts can be computed.
 */
class ContractorValidityService
{
    public function expiryWarningDays(): int
    {
        return (int) config('hrms.contractor_licence_expiry_warning_days', 30);
    }

    /**
     * Guard used before an Engagement is created with an active status, or
     * explicitly activated. Throws with field-specific messages so the
     * caller (Form Request or Service) can surface them the same way.
     */
    public function assertActivatable(ContractorBranchEngagement $engagement): void
    {
        $errors = [];

        if (! $engagement->contractor?->isActive()) {
            $errors['contractor'] = 'This Engagement cannot be activated because the Contractor is inactive.';
        }

        if (! $engagement->branch?->isActive()) {
            $errors['branch'] = 'This Engagement cannot be activated because the Branch is inactive.';
        }

        if ($engagement->isContractExpired()) {
            $errors['contract_end_date'] = 'This Engagement cannot be activated because the contract period has already ended.';
        }

        if ($engagement->isLicenceExpired()) {
            $errors['branch_licence_valid_to'] = 'This Engagement cannot be activated because the applicable labour licence has expired.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
