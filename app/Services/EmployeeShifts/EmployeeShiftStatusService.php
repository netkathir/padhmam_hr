<?php

namespace App\Services\EmployeeShifts;

use App\Models\EmployeeShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes Scheduled/Active/Completed status and the is_current flag from
 * effective dates (spec sections 18 and 35). Cancelled is never touched.
 * Reused by both the daily scheduled command and any on-demand recalculation.
 */
class EmployeeShiftStatusService
{
    /**
     * @return array{updated: int, activated: int, completed: int}
     */
    public function syncAll(?Carbon $today = null): array
    {
        $today = $today ?? now();
        $updated = 0;
        $activated = 0;
        $completed = 0;

        EmployeeShiftAssignment::query()
            ->withoutGlobalScopes()
            ->notCancelled()
            ->chunkById(200, function ($assignments) use ($today, &$updated, &$activated, &$completed): void {
                foreach ($assignments as $assignment) {
                    $newStatus = $assignment->computeStatus($today);

                    if ($newStatus === $assignment->status && $assignment->is_current === ($newStatus === EmployeeShiftAssignment::STATUS_ACTIVE)) {
                        continue;
                    }

                    DB::table('employee_shift_assignments')->where('id', $assignment->id)->update([
                        'status' => $newStatus,
                        'is_current' => $newStatus === EmployeeShiftAssignment::STATUS_ACTIVE,
                        'updated_at' => now(),
                    ]);

                    $updated++;

                    if ($newStatus === EmployeeShiftAssignment::STATUS_ACTIVE && $assignment->status !== EmployeeShiftAssignment::STATUS_ACTIVE) {
                        $activated++;
                    }

                    if ($newStatus === EmployeeShiftAssignment::STATUS_COMPLETED && $assignment->status !== EmployeeShiftAssignment::STATUS_COMPLETED) {
                        $completed++;
                    }
                }
            });

        $this->syncFixedShiftCache();

        return ['updated' => $updated, 'activated' => $activated, 'completed' => $completed];
    }

    /**
     * Keeps employees.fixed_shift_id in sync with whichever Fixed
     * assignment is now Active, for any Employee whose newly-Active Fixed
     * assignment does not yet match the cached column (e.g. a Scheduled
     * Fixed Shift Change that has just taken effect).
     */
    private function syncFixedShiftCache(): void
    {
        $current = DB::table('employee_shift_assignments')
            ->where('assignment_type', EmployeeShiftAssignment::TYPE_FIXED)
            ->where('status', EmployeeShiftAssignment::STATUS_ACTIVE)
            ->get(['employee_id', 'shift_id']);

        foreach ($current as $row) {
            DB::table('employees')
                ->where('id', $row->employee_id)
                ->where(fn ($q) => $q->whereNull('fixed_shift_id')->orWhere('fixed_shift_id', '!=', $row->shift_id))
                ->update(['fixed_shift_id' => $row->shift_id]);
        }
    }
}
