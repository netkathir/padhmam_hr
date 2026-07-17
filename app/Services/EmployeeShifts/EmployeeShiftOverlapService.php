<?php

namespace App\Services\EmployeeShifts;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Overlap prevention (spec sections 11 and 33). Callers must invoke this
 * from within a transaction with the Employee row and candidate existing
 * assignments already locked (see EmployeeShiftAssignmentService /
 * EmployeeShiftChangeService) — this service only performs the date-range
 * comparison, it does not lock anything itself.
 */
class EmployeeShiftOverlapService
{
    /**
     * Temporary assignments may only overlap Temporary assignments (never
     * more than one Temporary period at a time); Fixed/Rotational
     * ("regular") assignments may only overlap other regular assignments.
     * A Temporary assignment is explicitly allowed to overlap a regular
     * assignment — that is the whole point of a temporary override.
     */
    public function assertNoOverlap(
        Employee $employee,
        string $assignmentType,
        Carbon $from,
        ?Carbon $to,
        ?int $excludeAssignmentId = null,
    ): void {
        $conflictingTypes = $assignmentType === EmployeeShiftAssignment::TYPE_TEMPORARY
            ? [EmployeeShiftAssignment::TYPE_TEMPORARY]
            : [EmployeeShiftAssignment::TYPE_FIXED, EmployeeShiftAssignment::TYPE_ROTATIONAL];

        $candidates = EmployeeShiftAssignment::query()
            ->withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereIn('assignment_type', $conflictingTypes)
            ->whereIn('status', [EmployeeShiftAssignment::STATUS_SCHEDULED, EmployeeShiftAssignment::STATUS_ACTIVE])
            ->when($excludeAssignmentId, fn ($q) => $q->where('id', '!=', $excludeAssignmentId))
            ->get();

        foreach ($candidates as $existing) {
            if ($existing->overlapsRange($from, $to)) {
                $range = $existing->effective_from->format('d-m-Y').' to '.($existing->effective_to?->format('d-m-Y') ?? 'open-ended');

                throw ValidationException::withMessages([
                    'effective_from' => "This assignment overlaps an existing {$existing->assignment_type} assignment ({$range}).",
                ]);
            }
        }
    }
}
