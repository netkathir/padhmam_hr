<?php

namespace App\Http\Requests\Masters;

use App\Models\EmployeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeType|null $employeeType */
        $employeeType = $this->route('employeeType');

        return $employeeType ? $this->user()?->can('update', $employeeType) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : $this->input('name'),
            'employee_number_prefix' => $this->filled('employee_number_prefix')
                ? strtoupper(trim((string) $this->input('employee_number_prefix')))
                : null,
            'attendance_applicable' => $this->boolean('attendance_applicable'),
            'leave_applicable' => $this->boolean('leave_applicable'),
            'payroll_applicable' => $this->boolean('payroll_applicable'),
            'overtime_applicable' => $this->boolean('overtime_applicable'),
        ]);
    }

    public function rules(): array
    {
        /** @var EmployeeType $employeeType */
        $employeeType = $this->route('employeeType');

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('employee_types', 'name')->ignore($employeeType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'attendance_applicable' => ['required', 'boolean'],
            'leave_applicable' => ['required', 'boolean'],
            'payroll_applicable' => ['required', 'boolean'],
            'overtime_applicable' => ['required', 'boolean'],
            'default_shift_type' => ['required', Rule::in([EmployeeType::SHIFT_FIXED, EmployeeType::SHIFT_ROTATIONAL])],
            'employee_number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This Employee Type name is already in use.',
            'default_shift_type.in' => 'Default shift type must be Fixed or Rotational.',
            'employee_number_prefix.regex' => 'The employee number prefix may only contain letters, numbers, hyphens, and underscores.',
        ];
    }
}
