<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use App\Models\EmployeeSeparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SeparateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee ? $this->user()?->can('separate', $employee) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'separation_type' => ['required', Rule::in(array_keys(EmployeeSeparation::TYPES))],
            'last_working_date' => ['required', 'date'],
            'separation_reason' => ['required', 'string', 'max:1000'],
            'notice_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        $validator->after(function (Validator $validator) use ($employee): void {
            if ($this->filled('last_working_date') && $employee->date_of_joining
                && \Carbon\Carbon::parse($this->input('last_working_date'))->lt($employee->date_of_joining)) {
                $validator->errors()->add('last_working_date', 'The last working date must not be earlier than the date of joining.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'separation_type.in' => 'Select a valid separation type.',
        ];
    }
}
