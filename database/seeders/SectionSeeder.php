<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Section;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $branchContext = app(BranchContext::class);

        $sectionsByDepartment = [
            'HR' => [
                ['code' => 'RECRUIT', 'name' => 'Recruitment', 'order' => 1],
            ],
            'PROD' => [
                ['code' => 'CUTTING', 'name' => 'Cutting', 'order' => 1],
                ['code' => 'ASSY', 'name' => 'Assembly', 'order' => 2],
                ['code' => 'FINISH', 'name' => 'Finishing', 'order' => 3],
                ['code' => 'PACK', 'name' => 'Packing', 'order' => 4],
            ],
            'QA' => [
                ['code' => 'INSPECT', 'name' => 'Inspection', 'order' => 1],
            ],
            'MAINT' => [
                ['code' => 'ELECMAINT', 'name' => 'Electrical Maintenance', 'order' => 1],
            ],
        ];

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            foreach ($sectionsByDepartment as $departmentCode => $sections) {
                $department = Department::query()->where('department_code', $departmentCode)->first();

                if (! $department) {
                    continue;
                }

                foreach ($sections as $section) {
                    Section::query()->updateOrCreate(
                        ['department_id' => $department->id, 'section_code' => $section['code']],
                        [
                            'section_name' => $section['name'],
                            'display_order' => $section['order'],
                            'status' => 'active',
                        ]
                    );
                }
            }
        }

        $branchContext->clearBranch();
    }
}
