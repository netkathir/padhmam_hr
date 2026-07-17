<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Models\Organization;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;

class ContractorSeeder extends Seeder
{
    /**
     * Idempotent demo data: one Contractor with an active Engagement at the
     * Factory Unit branch, plus an expired Engagement at the Head Office
     * branch for testing expiry-related display states. Statutory numbers
     * below are clearly-marked placeholders, not real registrations.
     */
    public function run(): void
    {
        $organization = Organization::query()->sole();
        $branchContext = app(BranchContext::class);

        $contractor = Contractor::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'contractor_code' => 'SAMPLE-CONT-01'],
            [
                'legal_name' => 'Sample Manpower Services (Demo)',
                'trade_name' => 'Sample Manpower',
                'contractor_type' => 'partnership',
                'contact_person_name' => 'Demo Contact Person',
                'primary_phone' => '9000000001',
                'primary_email' => 'demo.contractor@example.test',
                'address_line_1' => 'Demo Industrial Estate',
                'city' => 'Coimbatore',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '641001',
                'pan_number' => 'AAAAA0000A',
                'pf_registration_number' => 'DEMO-PF-0001',
                'esi_registration_number' => 'DEMO-ESI-0001',
                'labour_licence_number' => 'DEMO-LICENCE-0001',
                'labour_licence_valid_from' => now()->subYear(),
                'labour_licence_valid_to' => now()->addYear(),
                'description' => 'Seeded sample contractor for development and demonstration only.',
                'status' => 'active',
            ]
        );

        $factoryUnit = Branch::query()->where('branch_code', 'FU')->first();
        $headOffice = Branch::query()->where('branch_code', 'HO')->first();

        if ($factoryUnit) {
            $branchContext->setBranch($factoryUnit);

            ContractorBranchEngagement::query()->updateOrCreate(
                ['contractor_id' => $contractor->id, 'branch_id' => $factoryUnit->id],
                [
                    'agreement_number' => 'AGMT-DEMO-FU-01',
                    'agreement_date' => now()->subYear(),
                    'contract_start_date' => now()->subYear(),
                    'contract_end_date' => null,
                    'maximum_labour_count' => 25,
                    'contact_person_name' => 'Demo Branch Coordinator',
                    'contact_person_phone' => '9000000002',
                    'remarks' => 'Seeded active sample engagement for development and demonstration only.',
                    'status' => 'active',
                ]
            );
        }

        if ($headOffice) {
            $branchContext->setBranch($headOffice);

            ContractorBranchEngagement::query()->updateOrCreate(
                ['contractor_id' => $contractor->id, 'branch_id' => $headOffice->id],
                [
                    'agreement_number' => 'AGMT-DEMO-HO-01',
                    'agreement_date' => now()->subYears(2),
                    'contract_start_date' => now()->subYears(2),
                    'contract_end_date' => now()->subMonth(),
                    'maximum_labour_count' => 10,
                    'contact_person_name' => 'Demo Branch Coordinator',
                    'contact_person_phone' => '9000000003',
                    'remarks' => 'Seeded expired sample engagement for testing expiry-related display states.',
                    'status' => 'inactive',
                ]
            );
        }

        $branchContext->clearBranch();
    }
}
