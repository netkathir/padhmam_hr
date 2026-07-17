<?php

namespace App\Http\Requests\EmployeeNumbering;

use App\Models\EmployeeNumberReservation;
use App\Models\EmployeeNumberSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdjustEmployeeNumberSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeNumberSequence|null $sequence */
        $sequence = $this->route('sequence');

        return $sequence ? $this->user()?->can('adjust', $sequence) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'next_number' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var EmployeeNumberSequence $sequence */
            $sequence = $this->route('sequence');
            $nextNumber = (int) $this->input('next_number');
            $rule = $sequence->rule;

            if ($nextNumber <= $sequence->last_issued_number) {
                $validator->errors()->add('next_number', 'The next number must be greater than the last issued number ('.$sequence->last_issued_number.').');
            }

            if ($rule && strlen((string) $nextNumber) > $rule->serial_number_length) {
                $validator->errors()->add('next_number', 'The next number does not fit within the configured serial number length ('.$rule->serial_number_length.' digits).');
            }

            $maxReservedSerial = EmployeeNumberReservation::query()
                ->where('employee_number_rule_id', $sequence->employee_number_rule_id)
                ->where('sequence_period', $sequence->sequence_period)
                ->where('status', EmployeeNumberReservation::STATUS_RESERVED)
                ->max('serial_number');

            if ($maxReservedSerial && $nextNumber <= $maxReservedSerial) {
                $validator->errors()->add('next_number', 'The next number must be greater than the highest currently reserved serial ('.$maxReservedSerial.').');
            }
        });
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for adjusting the sequence.',
        ];
    }
}
