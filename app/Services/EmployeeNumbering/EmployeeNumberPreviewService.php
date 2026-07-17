<?php

namespace App\Services\EmployeeNumbering;

use App\Models\Branch;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeNumberSequence;
use App\Models\EmployeeType;
use App\Models\Organization;
use Carbon\Carbon;

/**
 * Read-only preview builder. Never touches the sequence table — the serial
 * shown is either the rule's configured starting number (new/unsaved rule)
 * or the current next_number for the resolved period (existing rule),
 * inspected without a lock since nothing is being consumed.
 */
class EmployeeNumberPreviewService
{
    public function previewForRule(EmployeeNumberRule $rule, ?Carbon $date = null): array
    {
        $date ??= now();
        $organization = Organization::query()->sole();
        $period = $rule->sequencePeriodFor($date, $organization);

        $sequence = EmployeeNumberSequence::query()
            ->where('employee_number_rule_id', $rule->id)
            ->where('sequence_period', $period)
            ->first();

        $serial = $sequence?->next_number ?? $rule->starting_number;

        return $this->buildPreview($rule, $rule->branch, $date, $period, $serial);
    }

    /**
     * Builds a preview from raw (not-yet-persisted) form attributes, used by
     * the dynamic preview on the Create Rule screen before the rule exists.
     */
    public function previewFromAttributes(array $attributes, Branch $branch, EmployeeType $employeeType, ?Carbon $date = null): array
    {
        $date ??= now();
        $organization = Organization::query()->sole();

        $transient = new EmployeeNumberRule([
            ...$attributes,
            'employee_type_id' => $employeeType->id,
        ]);

        if ($transient->include_employee_type_prefix && ! $transient->employee_type_prefix) {
            $transient->employee_type_prefix = $employeeType->employee_number_prefix;
        }

        $period = $transient->sequencePeriodFor($date, $organization);
        $serial = $attributes['starting_number'] ?? 1;

        return $this->buildPreview($transient, $branch, $date, $period, (int) $serial);
    }

    private function buildPreview(EmployeeNumberRule $rule, ?Branch $branch, Carbon $date, string $period, int $serial): array
    {
        $branch ??= new Branch(['branch_code' => '']);
        $components = $rule->buildComponents($serial, $date, $branch, $period);

        return [
            'components' => $components,
            'separator' => $rule->separator ?? '',
            'sequence_period' => $period,
            'serial' => $serial,
            'preview' => implode($rule->separator ?? '', $components),
        ];
    }
}
