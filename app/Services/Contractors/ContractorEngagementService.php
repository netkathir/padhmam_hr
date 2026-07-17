<?php

namespace App\Services\Contractors;

use App\Models\ContractorBranchEngagement;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorEngagementService
{
    private const AGREEMENT_KEYS = ['agreement_number'];

    private const CONTRACT_PERIOD_KEYS = ['agreement_date', 'contract_start_date', 'contract_end_date'];

    private const LABOUR_COUNT_KEYS = ['maximum_labour_count'];

    private const BRANCH_LICENCE_KEYS = ['branch_labour_licence_number', 'branch_licence_valid_from', 'branch_licence_valid_to'];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly ContractorValidityService $validityService,
    ) {
    }

    public function create(array $data, User $actor, Request $request): ContractorBranchEngagement
    {
        return DB::transaction(function () use ($data, $actor, $request): ContractorBranchEngagement {
            $engagement = ContractorBranchEngagement::create([
                ...$data,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if ($engagement->isActive()) {
                $this->validityService->assertActivatable($engagement->fresh(['contractor', 'branch']));
            }

            $this->auditService->record(
                'contractor_engagement_created',
                'contractor_engagement',
                $engagement,
                [],
                $engagement->fresh()->toArray(),
                $request,
            );

            return $engagement->fresh();
        });
    }

    public function update(ContractorBranchEngagement $engagement, array $data, User $actor, Request $request): ContractorBranchEngagement
    {
        return DB::transaction(function () use ($engagement, $data, $actor, $request): ContractorBranchEngagement {
            $old = $engagement->replicate()->toArray();

            $agreementChanged = $this->anyKeyChanged($engagement, $data, self::AGREEMENT_KEYS);
            $contractPeriodChanged = $this->anyKeyChanged($engagement, $data, self::CONTRACT_PERIOD_KEYS);
            $labourCountChanged = $this->anyKeyChanged($engagement, $data, self::LABOUR_COUNT_KEYS);
            $branchLicenceChanged = $this->anyKeyChanged($engagement, $data, self::BRANCH_LICENCE_KEYS);

            $engagement->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $engagement->save();

            if ($engagement->isActive()) {
                $this->validityService->assertActivatable($engagement->fresh(['contractor', 'branch']));
            }

            $new = $engagement->fresh()->toArray();

            $events = array_filter([
                $agreementChanged ? 'contractor_engagement_agreement_number_changed' : null,
                $contractPeriodChanged ? 'contractor_engagement_contract_period_changed' : null,
                $labourCountChanged ? 'contractor_engagement_maximum_labour_count_changed' : null,
                $branchLicenceChanged ? 'contractor_engagement_branch_licence_changed' : null,
            ]);

            if ($events === []) {
                $events = ['contractor_engagement_updated'];
            }

            foreach ($events as $event) {
                $this->auditService->record($event, 'contractor_engagement', $engagement, $old, $new, $request);
            }

            return $engagement->fresh();
        });
    }

    public function activate(ContractorBranchEngagement $engagement, User $actor, Request $request): ContractorBranchEngagement
    {
        $this->validityService->assertActivatable($engagement->fresh(['contractor', 'branch']));

        return DB::transaction(function () use ($engagement, $actor, $request): ContractorBranchEngagement {
            $old = $engagement->replicate()->toArray();

            $engagement->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('contractor_engagement_activated', 'contractor_engagement', $engagement, $old, $engagement->fresh()->toArray(), $request);

            return $engagement->fresh();
        });
    }

    /**
     * Employee/attendance/payroll dependency checks will be enforced once
     * the Employee Registration module exists. For now, inactivation is
     * allowed unconditionally as documented in the module scope.
     */
    public function inactivate(ContractorBranchEngagement $engagement, User $actor, Request $request): ContractorBranchEngagement
    {
        return DB::transaction(function () use ($engagement, $actor, $request): ContractorBranchEngagement {
            $old = $engagement->replicate()->toArray();

            $engagement->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('contractor_engagement_inactivated', 'contractor_engagement', $engagement, $old, $engagement->fresh()->toArray(), $request);

            return $engagement->fresh();
        });
    }

    private function anyKeyChanged(ContractorBranchEngagement $engagement, array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && (string) $data[$key] !== (string) $engagement->getOriginal($key)) {
                return true;
            }
        }

        return false;
    }
}
