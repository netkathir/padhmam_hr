<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeType;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;

class EmployeeNumberRuleSeeder extends Seeder
{
    /**
     * Idempotent development seeder. These are illustrative development
     * formats only, not final production numbering formats — administrators
     * are expected to review and activate rules deliberately rather than
     * inherit these as-is. Seeded rules are created as Draft: activation
     * runs collision/overlap validation and initializes a live sequence, so
     * it is intentionally left to an explicit administrative action rather
     * than performed silently during seeding.
     */
    public function run(): void
    {
        $branchContext = app(BranchContext::class);

        $definitions = [
            EmployeeType::STAFF => ['prefix' => 'STF'],
            EmployeeType::COMPANY_LABOUR => ['prefix' => 'CL'],
            EmployeeType::CONTRACT_LABOUR => ['prefix' => 'CTL'],
        ];

        $employeeTypes = EmployeeType::query()->whereIn('code', array_keys($definitions))->get()->keyBy('code');

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            foreach ($definitions as $code => $definition) {
                $employeeType = $employeeTypes->get($code);

                if (! $employeeType) {
                    continue;
                }

                EmployeeNumberRule::query()->updateOrCreate(
                    ['employee_type_id' => $employeeType->id, 'rule_name' => $employeeType->name.' Numbering - '.$branch->branch_name],
                    [
                        'prefix' => $definition['prefix'],
                        'include_branch_code' => true,
                        'include_employee_type_prefix' => false,
                        'employee_type_prefix' => null,
                        'include_year' => true,
                        'year_format' => EmployeeNumberRule::YEAR_FORMAT_YYYY,
                        'separator' => '-',
                        'serial_number_length' => 4,
                        'starting_number' => 1,
                        'reset_frequency' => EmployeeNumberRule::RESET_YEARLY,
                        'effective_from' => now()->startOfYear()->toDateString(),
                        'effective_to' => null,
                        'is_default' => true,
                        'status' => EmployeeNumberRule::STATUS_DRAFT,
                        'description' => 'Development sample rule. Review and activate deliberately.',
                    ]
                );
            }
        }

        $branchContext->clearBranch();
    }
}
