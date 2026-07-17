<?php

namespace App\Services\EmployeeNumbering;

use App\Models\EmployeeNumberRule;
use App\Models\EmployeeType;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeNumberRuleService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeNumberSequenceService $sequenceService,
        private readonly EmployeeNumberCollisionService $collisionService,
    ) {
    }

    public function create(array $data, int $branchId, User $actor, Request $request): EmployeeNumberRule
    {
        $employeeType = EmployeeType::query()->findOrFail($data['employee_type_id']);

        if (($data['include_employee_type_prefix'] ?? false) && empty($data['employee_type_prefix'])) {
            $data['employee_type_prefix'] = $employeeType->employee_number_prefix;
        }

        return DB::transaction(function () use ($data, $branchId, $actor, $request): EmployeeNumberRule {
            $rule = EmployeeNumberRule::create([
                ...$data,
                'branch_id' => $branchId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('employee_number_rule_created', 'employee_number_rule', $rule, [], $rule->fresh()->toArray(), $request);

            return $rule;
        });
    }

    public function update(EmployeeNumberRule $rule, array $data, User $actor, Request $request): EmployeeNumberRule
    {
        if (! $rule->hasIssuedNumbers() && ($data['include_employee_type_prefix'] ?? false) && empty($data['employee_type_prefix'])) {
            $employeeType = EmployeeType::query()->find($data['employee_type_id'] ?? $rule->employee_type_id);
            $data['employee_type_prefix'] = $employeeType?->employee_number_prefix;
        }

        return DB::transaction(function () use ($rule, $data, $actor, $request): EmployeeNumberRule {
            $old = $rule->replicate()->toArray();
            $criticalChanged = ! $rule->hasIssuedNumbers() && $this->criticalFieldsChanged($rule, $data);
            $effectivePeriodChanged = array_key_exists('effective_from', $data) || array_key_exists('effective_to', $data);

            $rule->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $rule->save();

            if ($rule->isActive() && $effectivePeriodChanged) {
                $this->assertNoOverlap($rule);
            }

            $event = match (true) {
                $criticalChanged => 'employee_number_rule_format_changed',
                $effectivePeriodChanged => 'employee_number_rule_effective_period_changed',
                default => 'employee_number_rule_updated',
            };

            $this->auditService->record($event, 'employee_number_rule', $rule, $old, $rule->fresh()->toArray(), $request);

            return $rule->fresh();
        });
    }

    public function activate(EmployeeNumberRule $rule, User $actor, Request $request): EmployeeNumberRule
    {
        $this->assertActivatable($rule, $request);

        return DB::transaction(function () use ($rule, $actor, $request): EmployeeNumberRule {
            $old = $rule->replicate()->toArray();

            $rule->update([
                'status' => EmployeeNumberRule::STATUS_ACTIVE,
                'updated_by' => $actor->id,
            ]);

            $organization = Organization::query()->sole();
            $referenceDate = $rule->effective_from->isFuture() ? $rule->effective_from : now();
            $period = $rule->sequencePeriodFor($referenceDate, $organization);
            $this->sequenceService->initializeSequence($rule, $period);

            $this->auditService->record('employee_number_rule_activated', 'employee_number_rule', $rule, $old, $rule->fresh()->toArray(), $request);

            return $rule->fresh();
        });
    }

    public function inactivate(EmployeeNumberRule $rule, ?string $reason, User $actor, Request $request): EmployeeNumberRule
    {
        return DB::transaction(function () use ($rule, $reason, $actor, $request): EmployeeNumberRule {
            $old = $rule->replicate()->toArray();

            $rule->update([
                'status' => EmployeeNumberRule::STATUS_INACTIVE,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'employee_number_rule_inactivated',
                'employee_number_rule',
                $rule,
                $old,
                [...$rule->fresh()->toArray(), 'reason' => $reason],
                $request,
            );

            return $rule->fresh();
        });
    }

    public function createNewVersion(EmployeeNumberRule $rule, User $actor, Request $request): EmployeeNumberRule
    {
        return DB::transaction(function () use ($rule, $actor, $request): EmployeeNumberRule {
            $copy = EmployeeNumberRule::create([
                'branch_id' => $rule->branch_id,
                'employee_type_id' => $rule->employee_type_id,
                'rule_name' => $rule->rule_name.' (New Version)',
                'prefix' => $rule->prefix,
                'include_branch_code' => $rule->include_branch_code,
                'include_employee_type_prefix' => $rule->include_employee_type_prefix,
                'employee_type_prefix' => $rule->employee_type_prefix,
                'include_year' => $rule->include_year,
                'year_format' => $rule->year_format,
                'separator' => $rule->separator,
                'serial_number_length' => $rule->serial_number_length,
                'starting_number' => $rule->starting_number,
                'reset_frequency' => $rule->reset_frequency,
                'effective_from' => now()->addDay()->toDateString(),
                'effective_to' => null,
                'is_default' => $rule->is_default,
                'status' => EmployeeNumberRule::STATUS_DRAFT,
                'description' => $rule->description,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('employee_number_rule_version_created', 'employee_number_rule', $copy, [], $copy->fresh()->toArray(), $request);

            return $copy;
        });
    }

    private function assertActivatable(EmployeeNumberRule $rule, Request $request): void
    {
        if (! $rule->branch?->isActive()) {
            $this->blockActivation($rule, $request, 'This rule cannot be activated because its Branch is inactive.', 'branch');
        }

        if (! $rule->employeeType?->isActive()) {
            $this->blockActivation($rule, $request, 'This rule cannot be activated because its Employee Type is inactive.', 'employee_type');
        }

        if ($rule->effective_to && $rule->effective_to->lt($rule->effective_from)) {
            $this->blockActivation($rule, $request, 'The effective-to date must not be earlier than the effective-from date.', 'effective_to');
        }

        $this->assertNoOverlap($rule, $request);

        if ($collision = $this->collisionService->detectDefiniteCollision($rule)) {
            $this->blockActivation(
                $rule,
                $request,
                "This rule's format would collide with the active rule \"{$collision->rule_name}\" ({$collision->branch?->branch_name}). Include the Branch Code or Employee Type Prefix to make the format unique.",
                'prefix',
            );
        }
    }

    private function assertNoOverlap(EmployeeNumberRule $rule, ?Request $request = null): void
    {
        $others = EmployeeNumberRule::query()
            ->withoutGlobalScopes()
            ->where('id', '!=', $rule->id ?? 0)
            ->where('branch_id', $rule->branch_id)
            ->where('employee_type_id', $rule->employee_type_id)
            ->active()
            ->get();

        foreach ($others as $other) {
            if (! $this->periodsOverlap($rule, $other)) {
                continue;
            }

            $message = "This rule's effective period overlaps with the active rule \"{$other->rule_name}\".";

            if ($request) {
                $this->blockActivation($rule, $request, $message, 'effective_from');
            }

            throw ValidationException::withMessages(['effective_from' => $message]);
        }
    }

    private function periodsOverlap(EmployeeNumberRule $a, EmployeeNumberRule $b): bool
    {
        $startsBeforeOtherEnds = $b->effective_to === null || $a->effective_from->lte($b->effective_to);
        $otherStartsBeforeEnds = $a->effective_to === null || $b->effective_from->lte($a->effective_to);

        return $startsBeforeOtherEnds && $otherStartsBeforeEnds;
    }

    private function blockActivation(EmployeeNumberRule $rule, Request $request, string $message, string $field): void
    {
        $this->auditService->record('employee_number_rule_activation_blocked', 'employee_number_rule', $rule, [], ['reason' => $message], $request);

        throw ValidationException::withMessages([$field => $message]);
    }

    private function criticalFieldsChanged(EmployeeNumberRule $rule, array $data): bool
    {
        foreach (EmployeeNumberRule::CRITICAL_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if ((string) $data[$field] !== (string) $rule->getOriginal($field)) {
                return true;
            }
        }

        return false;
    }
}
