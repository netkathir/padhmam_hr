<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use Carbon\Carbon;

/**
 * Single source of truth for Shift duration math, shared by validation
 * (Form Requests), persistence (ShiftService), and the dynamic preview
 * endpoint — so the calculation is never duplicated across those layers.
 * Submitted is_overnight / gross / scheduled values are never trusted; they
 * are always recomputed here from start_time, end_time, and break minutes.
 */
class ShiftTimingService
{
    public function isOvernight(Carbon $start, Carbon $end): bool
    {
        return Shift::calculateIsOvernight($start, $end);
    }

    public function grossMinutes(Carbon $start, Carbon $end): int
    {
        return Shift::calculateGrossMinutes($start, $end);
    }

    public function scheduledWorkMinutes(int $grossMinutes, int $breakMinutes): int
    {
        return Shift::calculateScheduledWorkMinutes($grossMinutes, $breakMinutes);
    }

    /**
     * @return array{is_overnight: bool, gross_shift_minutes: int, break_duration_minutes: int, scheduled_work_minutes: int}
     */
    public function computeTiming(Carbon $start, Carbon $end, int $breakMinutes): array
    {
        $gross = $this->grossMinutes($start, $end);

        return [
            'is_overnight' => $this->isOvernight($start, $end),
            'gross_shift_minutes' => $gross,
            'break_duration_minutes' => $breakMinutes,
            'scheduled_work_minutes' => $this->scheduledWorkMinutes($gross, $breakMinutes),
        ];
    }
}
