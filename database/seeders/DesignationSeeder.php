<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Section;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $branchContext = app(BranchContext::class);

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            Designation::query()->updateOrCreate(
                ['designation_code' => 'GM'],
                [
                    'department_id' => null,
                    'section_id' => null,
                    'designation_name' => 'General Manager',
                    'hierarchy_level' => 1,
                    'display_order' => 1,
                    'status' => 'active',
                ]
            );

            $hr = Department::query()->where('department_code', 'HR')->first();
            $production = Department::query()->where('department_code', 'PROD')->first();
            $qa = Department::query()->where('department_code', 'QA')->first();
            $maintenance = Department::query()->where('department_code', 'MAINT')->first();

            if ($hr) {
                Designation::query()->updateOrCreate(
                    ['designation_code' => 'HRM'],
                    [
                        'department_id' => $hr->id,
                        'section_id' => null,
                        'designation_name' => 'HR Manager',
                        'hierarchy_level' => 2,
                        'display_order' => 1,
                        'status' => 'active',
                    ]
                );
            }

            if ($production) {
                Designation::query()->updateOrCreate(
                    ['designation_code' => 'PRODMGR'],
                    [
                        'department_id' => $production->id,
                        'section_id' => null,
                        'designation_name' => 'Production Manager',
                        'hierarchy_level' => 2,
                        'display_order' => 2,
                        'status' => 'active',
                    ]
                );

                $assembly = Section::query()->where('department_id', $production->id)->where('section_code', 'ASSY')->first();

                if ($assembly) {
                    Designation::query()->updateOrCreate(
                        ['designation_code' => 'SUP'],
                        [
                            'department_id' => $production->id,
                            'section_id' => $assembly->id,
                            'designation_name' => 'Supervisor',
                            'hierarchy_level' => 3,
                            'display_order' => 1,
                            'status' => 'active',
                        ]
                    );

                    Designation::query()->updateOrCreate(
                        ['designation_code' => 'MOP'],
                        [
                            'department_id' => $production->id,
                            'section_id' => $assembly->id,
                            'designation_name' => 'Machine Operator',
                            'hierarchy_level' => 5,
                            'display_order' => 2,
                            'status' => 'active',
                        ]
                    );
                }
            }

            if ($qa) {
                $inspection = Section::query()->where('department_id', $qa->id)->where('section_code', 'INSPECT')->first();

                if ($inspection) {
                    Designation::query()->updateOrCreate(
                        ['designation_code' => 'QI'],
                        [
                            'department_id' => $qa->id,
                            'section_id' => $inspection->id,
                            'designation_name' => 'Quality Inspector',
                            'hierarchy_level' => 4,
                            'display_order' => 1,
                            'status' => 'active',
                        ]
                    );
                }
            }

            if ($maintenance) {
                $electrical = Section::query()->where('department_id', $maintenance->id)->where('section_code', 'ELECMAINT')->first();

                if ($electrical) {
                    Designation::query()->updateOrCreate(
                        ['designation_code' => 'MT'],
                        [
                            'department_id' => $maintenance->id,
                            'section_id' => $electrical->id,
                            'designation_name' => 'Maintenance Technician',
                            'hierarchy_level' => 4,
                            'display_order' => 1,
                            'status' => 'active',
                        ]
                    );
                }
            }
        }

        $branchContext->clearBranch();
    }
}
