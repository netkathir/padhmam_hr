<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('organization_code', config('hrms.organization.code'))->firstOrFail();

        Branch::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'branch_code' => 'HO'],
            [
                'branch_name' => 'Head Office',
                'short_name' => 'HO',
                'branch_type' => Branch::TYPE_HEAD_OFFICE,
                'is_head_office' => true,
                'address_line_1' => 'Corporate Headquarters',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '600001',
                'timezone' => 'Asia/Kolkata',
                'display_order' => 1,
                'status' => 'active',
            ]
        );

        Branch::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'branch_code' => 'FU'],
            [
                'branch_name' => 'Factory Unit',
                'short_name' => 'Factory',
                'branch_type' => Branch::TYPE_FACTORY,
                'is_head_office' => false,
                'address_line_1' => 'Manufacturing Campus',
                'city' => 'Coimbatore',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '641001',
                'timezone' => 'Asia/Kolkata',
                'display_order' => 2,
                'status' => 'active',
            ]
        );
    }
}
