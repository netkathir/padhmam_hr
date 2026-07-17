<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['organization_code' => config('hrms.organization.code')],
            [
                'legal_name' => config('hrms.organization.legal_name'),
                'display_name' => config('hrms.organization.display_name'),
                'business_type' => 'Manufacturing',
                'financial_year_start_month' => 4,
                'address_line_1' => 'Registered Office',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '600001',
                'status' => 'active',
            ]
        );
    }
}
