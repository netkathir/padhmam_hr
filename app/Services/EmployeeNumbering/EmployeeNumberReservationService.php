<?php

namespace App\Services\EmployeeNumbering;

use App\Models\Branch;
use App\Models\EmployeeNumberReservation;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeType;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Owns the reservation lifecycle: reserve (consumes a serial), finalize
 * (called once the future Employee record is actually created), and cancel
 * (releases a reservation that was never finalized, e.g. Employee creation
 * failed). The serial itself is never reused once issued — a cancelled
 * reservation's number is retired, not recycled, so a displayed Employee
 * Number never silently changes meaning.
 */
class EmployeeNumberReservationService
{
    public function __construct(
        private readonly EmployeeNumberSequenceService $sequenceService,
        private readonly AuditService $auditService,
    ) {
    }

    public function reserve(
        EmployeeNumberRule $rule,
        Branch $branch,
        EmployeeType $employeeType,
        Carbon $effectiveDate,
        ?User $actor,
        Request $request,
    ): EmployeeNumberReservation {
        $organization = Organization::query()->sole();
        $period = $rule->sequencePeriodFor($effectiveDate, $organization);

        return DB::transaction(function () use ($rule, $branch, $employeeType, $effectiveDate, $period, $actor, $request): EmployeeNumberReservation {
            [, $serial] = $this->sequenceService->consumeNext($rule, $period);

            $employeeNumber = $rule->buildNumber($serial, $effectiveDate, $branch, $period);

            try {
                $reservation = EmployeeNumberReservation::create([
                    'employee_number_rule_id' => $rule->id,
                    'branch_id' => $branch->id,
                    'employee_type_id' => $employeeType->id,
                    'sequence_period' => $period,
                    'serial_number' => $serial,
                    'generated_employee_number' => $employeeNumber,
                    'reservation_token' => (string) Str::uuid(),
                    'reserved_by' => $actor?->id,
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(30),
                    'status' => EmployeeNumberReservation::STATUS_RESERVED,
                ]);
            } catch (QueryException $exception) {
                // The unique constraint on generated_employee_number is the
                // authoritative guard; a collision here means two rules
                // produced the same literal string despite collision
                // validation at activation time.
                throw ValidationException::withMessages([
                    'generated_employee_number' => 'The generated Employee Number is already in use. Please retry.',
                ]);
            }

            $this->auditService->record(
                'employee_number_reserved',
                'employee_number_sequence',
                $reservation,
                [],
                $reservation->toArray(),
                $request,
            );

            return $reservation;
        });
    }

    public function finalize(EmployeeNumberReservation $reservation, Request $request): EmployeeNumberReservation
    {
        return DB::transaction(function () use ($reservation, $request): EmployeeNumberReservation {
            $locked = EmployeeNumberReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => EmployeeNumberReservation::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);

            $this->auditService->record(
                'employee_number_finalized',
                'employee_number_sequence',
                $locked,
                [],
                ['generated_employee_number' => $locked->generated_employee_number],
                $request,
            );

            return $locked->fresh();
        });
    }

    public function cancel(EmployeeNumberReservation $reservation, Request $request): EmployeeNumberReservation
    {
        return DB::transaction(function () use ($reservation, $request): EmployeeNumberReservation {
            $locked = EmployeeNumberReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => EmployeeNumberReservation::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->auditService->record(
                'employee_number_reservation_cancelled',
                'employee_number_sequence',
                $locked,
                [],
                ['generated_employee_number' => $locked->generated_employee_number],
                $request,
            );

            return $locked->fresh();
        });
    }
}
