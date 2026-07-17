<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use Illuminate\Validation\ValidationException;

/**
 * Activation-readiness checks. Draft Shifts may be intentionally incomplete
 * (spec section 19), so these checks run only when a Shift is about to
 * become Active — not on every plain save.
 */
class ShiftValidityService
{
    public function assertActivatable(Shift $shift): void
    {
        $errors = [];

        if (! $shift->branch?->isActive()) {
            $errors['branch'] = 'This Shift cannot be activated because its Branch is inactive.';
        }

        if (! in_array($shift->shift_type, [Shift::TYPE_FIXED, Shift::TYPE_ROTATIONAL], true)) {
            $errors['shift_type'] = 'Shift type must be Fixed or Rotational.';
        }

        if ($shift->scheduled_work_minutes <= 0) {
            $errors['start_time'] = 'The scheduled work duration must be positive.';
        }

        if (empty($shift->applicable_days)) {
            $errors['applicable_days'] = 'At least one applicable day must be selected before activation.';
        }

        if ($shift->employeeTypes()->count() === 0) {
            $errors['employee_type_ids'] = 'At least one compatible Employee Type must be selected before activation.';
        }

        if ($shift->effective_to && $shift->effective_to->lt($shift->effective_from)) {
            $errors['effective_to'] = 'The effective-to date must not be earlier than the effective-from date.';
        }

        if ($shift->minimum_half_day_minutes && $shift->minimum_full_day_minutes
            && $shift->minimum_half_day_minutes >= $shift->minimum_full_day_minutes) {
            $errors['minimum_full_day_minutes'] = 'The full-day threshold must be greater than the half-day threshold.';
        }

        if ($shift->overtime_applicable === false && $shift->overtime_start_after_minutes) {
            $errors['overtime_start_after_minutes'] = 'Overtime start minutes must be empty when overtime is not applicable.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
