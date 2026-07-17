<?php

namespace App\Services\EmployeeNumbering;

use App\Models\EmployeeNumberReservation;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeNumberSequence;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeNumberSequenceService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    /**
     * Creates the sequence-period row for a rule if it does not already
     * exist. Safe to call repeatedly (e.g. on every activation) — an
     * existing period's counters are never reset or overwritten.
     */
    public function initializeSequence(EmployeeNumberRule $rule, string $period): EmployeeNumberSequence
    {
        $sequence = EmployeeNumberSequence::query()
            ->where('employee_number_rule_id', $rule->id)
            ->where('sequence_period', $period)
            ->first();

        if ($sequence) {
            return $sequence;
        }

        try {
            return EmployeeNumberSequence::create([
                'employee_number_rule_id' => $rule->id,
                'sequence_period' => $period,
                'last_issued_number' => 0,
                'next_number' => $rule->starting_number,
            ]);
        } catch (QueryException $exception) {
            // Lost a race to create the same period row concurrently; the
            // unique (rule_id, period) constraint means it now exists.
            return EmployeeNumberSequence::query()
                ->where('employee_number_rule_id', $rule->id)
                ->where('sequence_period', $period)
                ->firstOrFail();
        }
    }

    /**
     * Concurrency-safe: locks the sequence row for the duration of the
     * enclosing transaction so simultaneous Employee Registrations cannot
     * issue the same serial twice.
     *
     * @return array{0: EmployeeNumberSequence, 1: int}
     */
    public function consumeNext(EmployeeNumberRule $rule, string $period): array
    {
        $sequence = EmployeeNumberSequence::query()
            ->where('employee_number_rule_id', $rule->id)
            ->where('sequence_period', $period)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            $this->initializeSequence($rule, $period);

            $sequence = EmployeeNumberSequence::query()
                ->where('employee_number_rule_id', $rule->id)
                ->where('sequence_period', $period)
                ->lockForUpdate()
                ->first();
        }

        $serial = $sequence->next_number;

        if ($serial > $rule->maxSerialValue()) {
            throw ValidationException::withMessages([
                'serial_number' => 'The configured serial number length has been exhausted for this rule and period. Create a new rule version with a larger serial number length.',
            ]);
        }

        $sequence->update([
            'last_issued_number' => $serial,
            'next_number' => $serial + 1,
            'last_generated_at' => now(),
        ]);

        return [$sequence, $serial];
    }

    /**
     * Highly restricted manual correction, typically used once when
     * migrating serial numbers forward from a legacy HR system (see spec
     * section 28): set next_number to (highest existing external serial) +
     * 1 before the rule is used for real generation. Never allows moving
     * the counter backward below already-issued or currently-reserved
     * serials, and never permits a "reset to 1" shortcut.
     */
    public function adjustNextNumber(EmployeeNumberSequence $sequence, int $newNextNumber, string $reason, User $actor, Request $request): EmployeeNumberSequence
    {
        return DB::transaction(function () use ($sequence, $newNextNumber, $reason, $actor, $request): EmployeeNumberSequence {
            $locked = EmployeeNumberSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            $rule = $locked->rule;

            if ($newNextNumber <= $locked->last_issued_number) {
                $this->auditService->record('employee_number_sequence_adjustment_blocked', 'employee_number_sequence', $locked, [], ['attempted_next_number' => $newNextNumber, 'reason' => $reason], $request);

                throw ValidationException::withMessages([
                    'next_number' => 'The next number must be greater than the last issued number.',
                ]);
            }

            if ($rule && strlen((string) $newNextNumber) > $rule->serial_number_length) {
                $this->auditService->record('employee_number_sequence_adjustment_blocked', 'employee_number_sequence', $locked, [], ['attempted_next_number' => $newNextNumber, 'reason' => $reason], $request);

                throw ValidationException::withMessages([
                    'next_number' => 'The next number does not fit within the configured serial number length.',
                ]);
            }

            $maxReservedSerial = EmployeeNumberReservation::query()
                ->where('employee_number_rule_id', $locked->employee_number_rule_id)
                ->where('sequence_period', $locked->sequence_period)
                ->where('status', EmployeeNumberReservation::STATUS_RESERVED)
                ->max('serial_number');

            if ($maxReservedSerial && $newNextNumber <= $maxReservedSerial) {
                $this->auditService->record('employee_number_sequence_adjustment_blocked', 'employee_number_sequence', $locked, [], ['attempted_next_number' => $newNextNumber, 'reason' => $reason], $request);

                throw ValidationException::withMessages([
                    'next_number' => 'The next number must be greater than the highest currently reserved serial.',
                ]);
            }

            $old = $locked->replicate()->toArray();

            $locked->update(['next_number' => $newNextNumber]);

            $this->auditService->record(
                'employee_number_sequence_adjusted',
                'employee_number_sequence',
                $locked,
                $old,
                [...$locked->fresh()->toArray(), 'reason' => $reason],
                $request,
            );

            return $locked->fresh();
        });
    }
}
