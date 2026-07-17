<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\EmployeeType;
use App\Models\Shift;
use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Idempotent development seeder. These are illustrative development
     * shift definitions only, not final production timings — seeded
     * Shifts are left as Draft so an administrator reviews and activates
     * them deliberately, and re-running this seeder never overwrites an
     * administrator-edited production Shift's timings (updateOrCreate only
     * touches the fields listed below, keyed by the stable shift_code).
     */
    public function run(): void
    {
        $branchContext = app(BranchContext::class);

        $employeeTypesByCode = EmployeeType::query()
            ->whereIn('code', [EmployeeType::STAFF, EmployeeType::COMPANY_LABOUR, EmployeeType::CONTRACT_LABOUR])
            ->get()
            ->keyBy('code');

        $definitions = [
            [
                'code' => 'GEN',
                'name' => 'General Shift',
                'start' => '09:00',
                'end' => '18:00',
                'break' => 60,
                'types' => [EmployeeType::STAFF],
                'order' => 1,
            ],
            [
                'code' => 'SHIFT-A',
                'name' => 'First Shift',
                'start' => '06:00',
                'end' => '14:00',
                'break' => 30,
                'types' => [EmployeeType::COMPANY_LABOUR, EmployeeType::CONTRACT_LABOUR],
                'order' => 2,
            ],
            [
                'code' => 'SHIFT-B',
                'name' => 'Second Shift',
                'start' => '14:00',
                'end' => '22:00',
                'break' => 30,
                'types' => [EmployeeType::COMPANY_LABOUR, EmployeeType::CONTRACT_LABOUR],
                'order' => 3,
            ],
            [
                'code' => 'NIGHT',
                'name' => 'Night Shift',
                'start' => '22:00',
                'end' => '06:00',
                'break' => 30,
                'types' => [EmployeeType::COMPANY_LABOUR, EmployeeType::CONTRACT_LABOUR],
                'order' => 4,
            ],
        ];

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            foreach ($definitions as $definition) {
                $start = Carbon::createFromFormat('H:i', $definition['start']);
                $end = Carbon::createFromFormat('H:i', $definition['end']);
                $gross = Shift::calculateGrossMinutes($start, $end);

                $shift = Shift::query()->updateOrCreate(
                    ['shift_code' => $definition['code']],
                    [
                        'shift_name' => $definition['name'],
                        'shift_type' => $definition['code'] === 'GEN' ? Shift::TYPE_FIXED : Shift::TYPE_ROTATIONAL,
                        'start_time' => $definition['start'],
                        'end_time' => $definition['end'],
                        'is_overnight' => Shift::calculateIsOvernight($start, $end),
                        'gross_shift_minutes' => $gross,
                        'break_duration_minutes' => $definition['break'],
                        'scheduled_work_minutes' => Shift::calculateScheduledWorkMinutes($gross, $definition['break']),
                        'early_entry_allowed_minutes' => 15,
                        'late_entry_grace_minutes' => 10,
                        'early_exit_grace_minutes' => 10,
                        'late_exit_allowed_minutes' => 15,
                        'overtime_applicable' => false,
                        'applicable_days' => Shift::DAY_CODES,
                        'effective_from' => now()->startOfYear()->toDateString(),
                        'display_order' => $definition['order'],
                        'status' => Shift::STATUS_DRAFT,
                        'description' => 'Development sample shift. Review and activate deliberately.',
                    ]
                );

                $employeeTypeIds = collect($definition['types'])
                    ->map(fn ($code) => $employeeTypesByCode->get($code)?->id)
                    ->filter()
                    ->all();

                $shift->employeeTypes()->sync($employeeTypeIds);
            }
        }

        $branchContext->clearBranch();
    }
}
