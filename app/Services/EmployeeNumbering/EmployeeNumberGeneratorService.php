<?php

namespace App\Services\EmployeeNumbering;

use App\Models\Branch;
use App\Models\EmployeeNumberReservation;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The single entry point future Employee Registration must call to obtain
 * an Employee Number. Controllers and Blade views must never construct or
 * calculate an Employee Number themselves.
 *
 * Recommended integration (see spec section 24): reserve() inside the
 * Employee Registration transaction, create the Employee record, then call
 * finalize(). If Employee creation fails, let the transaction roll back —
 * the reservation row (and the serial it consumed) rolls back with it, so
 * failed attempts do not burn a number. Only reservations that survive
 * their own transaction but whose Employee never gets created should be
 * explicitly cancelled via EmployeeNumberReservationService::cancel().
 */
class EmployeeNumberGeneratorService
{
    public function __construct(private readonly EmployeeNumberReservationService $reservationService)
    {
    }

    public function reserve(Branch $branch, EmployeeType $employeeType, Carbon $effectiveDate, ?User $actor, Request $request): EmployeeNumberReservation
    {
        if (! $branch->isActive()) {
            throw ValidationException::withMessages([
                'branch' => 'Employee Numbers cannot be generated for an inactive Branch.',
            ]);
        }

        if (! $employeeType->isActive()) {
            throw ValidationException::withMessages([
                'employee_type' => 'Employee Numbers cannot be generated for an inactive Employee Type.',
            ]);
        }

        $rule = EmployeeNumberRule::resolveRule($branch->id, $employeeType->id, $effectiveDate);

        if (! $rule) {
            throw ValidationException::withMessages([
                'rule' => 'No active Employee Number Rule applies to this Branch and Employee Type for the given date.',
            ]);
        }

        return $this->reservationService->reserve($rule, $branch, $employeeType, $effectiveDate, $actor, $request);
    }
}
