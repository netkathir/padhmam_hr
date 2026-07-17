<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Clones a Shift's configuration into a new Draft Shift within the same
 * Branch. Code, name, status, and audit/ownership metadata are never
 * copied — the administrator must supply a distinguishing code and name,
 * and the clone always starts life as Draft regardless of the source
 * Shift's status.
 */
class ShiftCloneService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function clone(Shift $source, string $newShiftCode, string $newShiftName, User $actor, Request $request): Shift
    {
        return DB::transaction(function () use ($source, $newShiftCode, $newShiftName, $actor, $request): Shift {
            $clone = Shift::create([
                'branch_id' => $source->branch_id,
                'shift_code' => $newShiftCode,
                'shift_name' => $newShiftName,
                'short_name' => $source->short_name,
                'shift_type' => $source->shift_type,
                'start_time' => $source->getRawOriginal('start_time'),
                'end_time' => $source->getRawOriginal('end_time'),
                'is_overnight' => $source->is_overnight,
                'gross_shift_minutes' => $source->gross_shift_minutes,
                'break_duration_minutes' => $source->break_duration_minutes,
                'scheduled_work_minutes' => $source->scheduled_work_minutes,
                'early_entry_allowed_minutes' => $source->early_entry_allowed_minutes,
                'late_entry_grace_minutes' => $source->late_entry_grace_minutes,
                'early_exit_grace_minutes' => $source->early_exit_grace_minutes,
                'late_exit_allowed_minutes' => $source->late_exit_allowed_minutes,
                'minimum_half_day_minutes' => $source->minimum_half_day_minutes,
                'minimum_full_day_minutes' => $source->minimum_full_day_minutes,
                'overtime_applicable' => $source->overtime_applicable,
                'overtime_start_after_minutes' => $source->overtime_start_after_minutes,
                'applicable_days' => $source->applicable_days,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'color_code' => $source->color_code,
                'description' => $source->description,
                'display_order' => $source->display_order,
                'status' => Shift::STATUS_DRAFT,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $clone->employeeTypes()->sync($source->employeeTypes()->pluck('employee_types.id')->all());

            $this->auditService->record(
                'shift_cloned',
                'shift',
                $clone,
                ['cloned_from_shift_id' => $source->id, 'cloned_from_shift_code' => $source->shift_code],
                $clone->fresh()->toArray(),
                $request,
            );

            return $clone->fresh();
        });
    }
}
