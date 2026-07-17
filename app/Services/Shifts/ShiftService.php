<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /** @var array<string, list<string>> */
    private const CHANGE_EVENTS = [
        'shift_code_changed' => ['shift_code'],
        'shift_name_changed' => ['shift_name'],
        'shift_type_changed' => ['shift_type'],
        'shift_timing_changed' => ['start_time', 'end_time'],
        'shift_break_duration_changed' => ['break_duration_minutes'],
        'shift_grace_periods_changed' => ['early_entry_allowed_minutes', 'late_entry_grace_minutes', 'early_exit_grace_minutes', 'late_exit_allowed_minutes'],
        'shift_attendance_thresholds_changed' => ['minimum_half_day_minutes', 'minimum_full_day_minutes'],
        'shift_overtime_configuration_changed' => ['overtime_applicable', 'overtime_start_after_minutes'],
        'shift_applicable_days_changed' => ['applicable_days'],
        'shift_effective_period_changed' => ['effective_from', 'effective_to'],
    ];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly ShiftTimingService $timingService,
        private readonly ShiftValidityService $validityService,
    ) {
    }

    public function create(array $data, int $branchId, User $actor, Request $request): Shift
    {
        $employeeTypeIds = $data['employee_type_ids'] ?? [];
        unset($data['employee_type_ids']);

        $data = $this->applyDerivedFields($data);

        return DB::transaction(function () use ($data, $employeeTypeIds, $branchId, $actor, $request): Shift {
            $shift = Shift::create([
                ...$data,
                'branch_id' => $branchId,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $shift->employeeTypes()->sync($employeeTypeIds);

            $this->auditService->record('shift_created', 'shift', $shift, [], $shift->fresh()->toArray(), $request);

            return $shift->fresh();
        });
    }

    public function update(Shift $shift, array $data, User $actor, Request $request): Shift
    {
        $employeeTypeIds = array_key_exists('employee_type_ids', $data) ? $data['employee_type_ids'] : null;
        unset($data['employee_type_ids']);

        if (isset($data['start_time'], $data['end_time'])) {
            $data = $this->applyDerivedFields([...$data, 'break_duration_minutes' => $data['break_duration_minutes'] ?? $shift->break_duration_minutes]);
        }

        return DB::transaction(function () use ($shift, $data, $employeeTypeIds, $actor, $request): Shift {
            $old = $shift->replicate()->toArray();
            $oldEmployeeTypeIds = $shift->employeeTypes()->pluck('employee_types.id')->sort()->values()->all();

            $events = $this->changedEvents($shift, $data);

            $shift->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $shift->save();

            if ($shift->isActive() && (isset($data['effective_from']) || isset($data['effective_to']))) {
                $this->validityService->assertActivatable($shift->fresh());
            }

            if ($employeeTypeIds !== null) {
                $shift->employeeTypes()->sync($employeeTypeIds);

                $newEmployeeTypeIds = collect($employeeTypeIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($oldEmployeeTypeIds !== $newEmployeeTypeIds) {
                    $events[] = 'shift_employee_type_compatibility_changed';
                }
            }

            if ($events === []) {
                $events = ['shift_updated'];
            }

            $new = $shift->fresh()->toArray();

            foreach (array_unique($events) as $event) {
                $this->auditService->record($event, 'shift', $shift, $old, $new, $request);
            }

            return $shift->fresh();
        });
    }

    public function activate(Shift $shift, User $actor, Request $request): Shift
    {
        try {
            $this->validityService->assertActivatable($shift);
        } catch (ValidationException $exception) {
            $this->auditService->record('shift_activation_blocked', 'shift', $shift, [], ['errors' => $exception->errors()], $request);

            throw $exception;
        }

        return DB::transaction(function () use ($shift, $actor, $request): Shift {
            $old = $shift->replicate()->toArray();

            $shift->update([
                'status' => Shift::STATUS_ACTIVE,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('shift_activated', 'shift', $shift, $old, $shift->fresh()->toArray(), $request);

            return $shift->fresh();
        });
    }

    /**
     * Future dependency (employee fixed-shift assignments, rotational
     * assignments, open attendance periods, unprocessed attendance,
     * payroll processing) will block inactivation once those modules exist
     * — see Shift::hasOperationalUsage(). For now, inactivation is allowed
     * unconditionally, as documented in the module scope.
     */
    public function inactivate(Shift $shift, ?string $reason, User $actor, Request $request): Shift
    {
        return DB::transaction(function () use ($shift, $reason, $actor, $request): Shift {
            $old = $shift->replicate()->toArray();

            $shift->update([
                'status' => Shift::STATUS_INACTIVE,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record(
                'shift_inactivated',
                'shift',
                $shift,
                $old,
                [...$shift->fresh()->toArray(), 'reason' => $reason],
                $request,
            );

            return $shift->fresh();
        });
    }

    /**
     * @return array{is_overnight: bool, gross_shift_minutes: int, break_duration_minutes: int, scheduled_work_minutes: int}|array
     */
    private function applyDerivedFields(array $data): array
    {
        $timing = $this->timingService->computeTiming(
            Carbon::parse($data['start_time']),
            Carbon::parse($data['end_time']),
            (int) $data['break_duration_minutes'],
        );

        if (! ($data['overtime_applicable'] ?? false)) {
            $data['overtime_start_after_minutes'] = null;
        }

        if (isset($data['applicable_days'])) {
            $data['applicable_days'] = collect($data['applicable_days'])
                ->map(fn ($day) => strtoupper($day))
                ->unique()
                ->values()
                ->all();
        }

        return [...$data, ...$timing];
    }

    private function changedEvents(Shift $shift, array $data): array
    {
        $events = [];

        foreach (self::CHANGE_EVENTS as $event => $fields) {
            foreach ($fields as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                if ($this->valueChanged($shift, $field, $data[$field])) {
                    $events[] = $event;

                    break;
                }
            }
        }

        return $events;
    }

    /**
     * start_time/end_time carry a Carbon-returning custom accessor, so
     * getOriginal() would yield a Carbon anchored to "today" — comparing
     * that against a raw submitted "H:i" string would false-positive on
     * every save. Compare the raw stored "H:i:s" string instead.
     * applicable_days is compared as a normalized, order-independent set.
     */
    private function valueChanged(Shift $shift, string $field, mixed $newValue): bool
    {
        if (in_array($field, ['start_time', 'end_time'], true)) {
            return $shift->getRawOriginal($field) !== Carbon::parse($newValue)->format('H:i:s');
        }

        if ($field === 'applicable_days') {
            $old = $shift->getOriginal($field) ?? [];
            sort($old);

            $new = collect($newValue)->map(fn ($day) => strtoupper($day))->unique()->sort()->values()->all();

            return $old !== $new;
        }

        return (string) $shift->getOriginal($field) !== (string) $newValue;
    }
}
