<?php

namespace App\Services\EmployeeShifts;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The single authoritative place that answers "what Shift is this Employee
 * on for this date" (spec section 12). Future Attendance processing must
 * call resolveEmployeeShift() rather than reading employees.fixed_shift_id
 * or any cached flag directly.
 */
class EmployeeShiftResolutionService
{
    /**
     * Resolution priority: Temporary > Rotational > Fixed. Works correctly
     * even if the scheduled status-sync command has not run recently,
     * because it derives validity from effective dates rather than trusting
     * the stored status/is_current columns.
     */
    public function resolveEmployeeShift(Employee $employee, Carbon $date): ?EmployeeShiftAssignment
    {
        $effective = $this->effectiveAssignments($employee, $date);

        return $effective->first(fn (EmployeeShiftAssignment $a) => $a->isTemporary())
            ?? $effective->first(fn (EmployeeShiftAssignment $a) => $a->isRotational())
            ?? $effective->first(fn (EmployeeShiftAssignment $a) => $a->isFixed());
    }

    /**
     * @return Collection<int, EmployeeShiftAssignment>
     */
    public function effectiveAssignments(Employee $employee, Carbon $date): Collection
    {
        return EmployeeShiftAssignment::query()
            ->withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('branch_id', $employee->branch_id)
            ->notCancelled()
            ->with('shift')
            ->get()
            ->filter(fn (EmployeeShiftAssignment $a) => $a->isEffectiveOn($date) && $a->shift?->isActive());
    }

    /**
     * Flags data problems a properly-guarded overlap check should never
     * allow, but which a direct database edit could still introduce —
     * more than one non-cancelled "regular" (Fixed/Rotational) assignment
     * or more than one Temporary assignment effective on the same date.
     *
     * @return list<string>
     */
    public function detectInconsistencies(Employee $employee, Carbon $date): array
    {
        $effective = $this->effectiveAssignments($employee, $date);
        $warnings = [];

        $regular = $effective->filter(fn (EmployeeShiftAssignment $a) => ! $a->isTemporary());

        if ($regular->count() > 1) {
            $warnings[] = 'More than one regular (Fixed/Rotational) Shift assignment is effective on this date.';
        }

        $temporary = $effective->filter(fn (EmployeeShiftAssignment $a) => $a->isTemporary());

        if ($temporary->count() > 1) {
            $warnings[] = 'More than one Temporary Shift assignment is effective on this date.';
        }

        return $warnings;
    }

    public function nextScheduledAssignment(Employee $employee): ?EmployeeShiftAssignment
    {
        return EmployeeShiftAssignment::query()
            ->withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', EmployeeShiftAssignment::STATUS_SCHEDULED)
            ->orderBy('effective_from')
            ->first();
    }
}
