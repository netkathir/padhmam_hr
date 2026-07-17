<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $branchContext = app(BranchContext::class);

        $departments = [
            ['code' => 'HR', 'name' => 'Human Resources', 'short' => 'HR', 'order' => 1],
            ['code' => 'PROD', 'name' => 'Production', 'short' => 'Production', 'order' => 2],
            ['code' => 'QA', 'name' => 'Quality Assurance', 'short' => 'QA', 'order' => 3],
            ['code' => 'MAINT', 'name' => 'Maintenance', 'short' => 'Maintenance', 'order' => 4],
            ['code' => 'FIN', 'name' => 'Finance and Accounts', 'short' => 'Finance', 'order' => 5],
        ];

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            foreach ($departments as $department) {
                Department::query()->updateOrCreate(
                    ['department_code' => $department['code']],
                    [
                        'department_name' => $department['name'],
                        'short_name' => $department['short'],
                        'display_order' => $department['order'],
                        'status' => 'active',
                    ]
                );
            }
        }

        $branchContext->clearBranch();
    }
}
