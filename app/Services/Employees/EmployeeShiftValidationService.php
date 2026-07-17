<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmployeeShiftValidationService
{
    public function assertValid(string $shiftType, ?int $fixedShiftId, int $branchId, EmployeeType $employeeType, Carbon $dateOfJoining): void
    {
        if ($shiftType === Employee::SHIFT_TYPE_ROTATIONAL) {
            if ($fixedShiftId) {
                throw ValidationException::withMessages([
                    'fixed_shift_id' => 'A Fixed Shift must not be selected when Shift Type is Rotational.',
                ]);
            }

            return;
        }

        if (! $fixedShiftId) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'Select a Fixed Shift when Shift Type is Fixed.',
            ]);
        }

        $shift = Shift::query()->withoutGlobalScopes()->with('employeeTypes')->find($fixedShiftId);

        if (! $shift || $shift->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'The selected Fixed Shift does not belong to the active Branch.',
            ]);
        }

        if (! $shift->isActive()) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'The selected Fixed Shift is not Active.',
            ]);
        }

        if (! $shift->isFixed()) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'The selected Shift is not configured as a Fixed Shift.',
            ]);
        }

        if (! $shift->isEffectiveOn($dateOfJoining)) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'The selected Fixed Shift is not effective on the Date of Joining.',
            ]);
        }

        if (! $shift->supportsEmployeeType($employeeType)) {
            throw ValidationException::withMessages([
                'fixed_shift_id' => 'The selected Fixed Shift does not support this Employee Type.',
            ]);
        }
    }
}
