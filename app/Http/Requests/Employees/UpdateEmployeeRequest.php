<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee ? $this->user()?->can('update', $employee) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->filled('first_name') ? trim((string) $this->input('first_name')) : $this->input('first_name'),
            'middle_name' => $this->filled('middle_name') ? trim((string) $this->input('middle_name')) : null,
            'last_name' => $this->filled('last_name') ? trim((string) $this->input('last_name')) : null,
            'biometric_identifier' => $this->filled('biometric_identifier') ? trim((string) $this->input('biometric_identifier')) : null,
            'attendance_applicable' => $this->boolean('attendance_applicable'),
            'leave_applicable' => $this->boolean('leave_applicable'),
            'payroll_applicable' => $this->boolean('payroll_applicable'),
            'overtime_applicable' => $this->boolean('overtime_applicable'),
            'probation_applicable' => $this->boolean('probation_applicable'),
            'statutory' => [
                ...$this->input('statutory', []),
                'professional_tax_applicable' => $this->boolean('statutory.professional_tax_applicable', true),
                'pf_applicable' => $this->boolean('statutory.pf_applicable', true),
                'esi_applicable' => $this->boolean('statutory.esi_applicable', true),
                'tds_applicable' => $this->boolean('statutory.tds_applicable', true),
            ],
            'addresses' => [
                'current' => $this->input('addresses.current', []),
                'permanent' => [
                    ...$this->input('addresses.permanent', []),
                    'is_same_as_current' => $this->boolean('addresses.permanent.is_same_as_current'),
                ],
            ],
            'emergency_contacts' => collect($this->input('emergency_contacts', []))
                ->map(fn ($contact, $index) => [
                    ...$contact,
                    'is_primary' => $this->boolean("emergency_contacts.{$index}.is_primary"),
                ])
                ->all(),
        ]);
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $branchId = $employee->branch_id;
        $nameRegex = "/^[\\p{L}'\\-\\s]+$/u";

        return [
            // employee_type_id is intentionally absent: Employee Type cannot
            // be changed through normal edit once registration is complete
            // (spec section 34) — a future controlled Classification Change
            // action is the extension point for that.
            'first_name' => ['required', 'string', 'max:100', 'regex:'.$nameRegex],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:'.$nameRegex],
            'last_name' => ['nullable', 'string', 'max:100', 'regex:'.$nameRegex],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(array_keys(Employee::GENDERS))],
            'marital_status' => ['nullable', Rule::in(array_keys(Employee::MARITAL_STATUSES))],
            'blood_group' => ['nullable', Rule::in(Employee::BLOOD_GROUPS)],
            'nationality' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.config('hrms.employee_photo_max_kb')],

            'date_of_joining' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:date_of_joining'],
            'probation_applicable' => ['boolean'],
            'probation_period_days' => ['nullable', 'integer', 'min:1'],
            'probation_end_date' => ['nullable', 'date'],

            'department_id' => ['required', Rule::exists('departments', 'id')->where('branch_id', $branchId)],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('branch_id', $branchId)],
            'designation_id' => ['required', Rule::exists('designations', 'id')->where('branch_id', $branchId)],
            'reporting_manager_id' => [
                'nullable',
                'different:id',
                Rule::exists('employees', 'id')->where('branch_id', $branchId)->where('status', Employee::STATUS_ACTIVE),
            ],

            'shift_type' => ['required', Rule::in([Employee::SHIFT_TYPE_FIXED, Employee::SHIFT_TYPE_ROTATIONAL])],
            'fixed_shift_id' => [
                'nullable',
                'required_if:shift_type,'.Employee::SHIFT_TYPE_FIXED,
                Rule::exists('shifts', 'id')->where('branch_id', $branchId)->where('status', 'active'),
            ],
            'shift_type_override_reason' => ['nullable', 'string', 'max:500'],

            'contractor_id' => ['nullable', Rule::exists('contractors', 'id')->where('status', 'active')],
            'contractor_branch_engagement_id' => ['nullable', Rule::exists('contractor_branch_engagements', 'id')->where('branch_id', $branchId)],

            'biometric_identifier' => ['nullable', 'string', 'max:100', Rule::unique('employees', 'biometric_identifier')->ignore($employee->id)],

            'attendance_applicable' => ['boolean'],
            'leave_applicable' => ['boolean'],
            'payroll_applicable' => ['boolean'],
            'overtime_applicable' => ['boolean'],
            'applicability_override_reason' => ['nullable', 'string', 'max:500'],

            'change_reason' => ['nullable', 'string', 'max:500'],

            'contact.personal_mobile' => ['required', 'string', 'max:20'],
            'contact.alternate_mobile' => ['nullable', 'string', 'max:20'],
            'contact.personal_email' => ['nullable', 'email', 'max:150'],
            'contact.official_email' => ['nullable', 'email', 'max:150', Rule::unique('employee_contacts', 'official_email')->ignore($employee->contact?->id)],

            'addresses.current.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.current.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.current.city' => ['required', 'string', 'max:100'],
            'addresses.current.district' => ['nullable', 'string', 'max:100'],
            'addresses.current.state' => ['required', 'string', 'max:100'],
            'addresses.current.country' => ['required', 'string', 'max:100'],
            'addresses.current.postal_code' => ['required', 'string', 'max:20'],
            'addresses.permanent.is_same_as_current' => ['boolean'],
            'addresses.permanent.address_line_1' => ['required_if:addresses.permanent.is_same_as_current,0', 'nullable', 'string', 'max:255'],
            'addresses.permanent.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.city' => ['required_if:addresses.permanent.is_same_as_current,0', 'nullable', 'string', 'max:100'],
            'addresses.permanent.district' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.state' => ['required_if:addresses.permanent.is_same_as_current,0', 'nullable', 'string', 'max:100'],
            'addresses.permanent.country' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.postal_code' => ['required_if:addresses.permanent.is_same_as_current,0', 'nullable', 'string', 'max:20'],

            'statutory.aadhaar_number' => ['nullable', 'digits:12'],
            'statutory.pan_number' => ['nullable', 'string', 'max:20'],
            'statutory.uan_number' => ['nullable', 'string', 'max:20'],
            'statutory.pf_number' => ['nullable', 'string', 'max:30'],
            'statutory.esi_number' => ['nullable', 'string', 'max:30'],
            'statutory.professional_tax_applicable' => ['boolean'],
            'statutory.pf_applicable' => ['boolean'],
            'statutory.esi_applicable' => ['boolean'],
            'statutory.tds_applicable' => ['boolean'],

            'bank.account_holder_name' => ['nullable', 'string', 'max:150'],
            'bank.bank_name' => ['nullable', 'string', 'max:150'],
            'bank.branch_name' => ['nullable', 'string', 'max:150'],
            'bank.account_number' => ['nullable', 'string', 'max:30'],
            'bank.account_type' => ['nullable', Rule::in(['savings', 'current', 'other'])],
            'bank.ifsc_code' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],

            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.name' => ['required_with:emergency_contacts.*.primary_phone', 'nullable', 'string', 'max:150'],
            'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contacts.*.primary_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contacts.*.alternate_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contacts.*.address' => ['nullable', 'string', 'max:500'],
            'emergency_contacts.*.is_primary' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        $validator->after(function (Validator $validator) use ($employee): void {
            if ($this->filled('section_id') && $this->filled('department_id')) {
                $section = Section::query()->find($this->input('section_id'));

                if ($section && $section->department_id !== (int) $this->input('department_id')) {
                    $validator->errors()->add('section_id', 'The selected Section does not belong to the selected Department.');
                }
            }

            if ($this->input('shift_type') === Employee::SHIFT_TYPE_ROTATIONAL && $this->filled('fixed_shift_id')) {
                $validator->errors()->add('fixed_shift_id', 'A Fixed Shift must not be selected when Shift Type is Rotational.');
            }

            if ($employee->hasCompletedRegistration() && $this->criticalFieldsChanged($employee) && ! $this->filled('change_reason')) {
                $validator->errors()->add('change_reason', 'A reason is required when changing Department, Section, Designation, Contractor, Date of Joining, or Shift Type.');
            }
        });
    }

    private function criticalFieldsChanged(Employee $employee): bool
    {
        $checks = [
            'date_of_joining' => optional($employee->date_of_joining)->toDateString(),
            'contractor_id' => $employee->contractor_id,
            'contractor_branch_engagement_id' => $employee->contractor_branch_engagement_id,
            'department_id' => $employee->department_id,
            'section_id' => $employee->section_id,
            'designation_id' => $employee->designation_id,
            'shift_type' => $employee->shift_type,
        ];

        foreach ($checks as $field => $currentValue) {
            $submitted = $this->input($field);
            $submitted = $field === 'date_of_joining' && $submitted ? \Carbon\Carbon::parse($submitted)->toDateString() : $submitted;

            if ((string) $submitted !== (string) $currentValue) {
                return true;
            }
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'Select a Department that belongs to the active Branch.',
            'designation_id.exists' => 'Select a Designation that belongs to the active Branch.',
            'reporting_manager_id.exists' => 'The Reporting Manager must be an active Employee in the same Branch.',
            'reporting_manager_id.different' => 'An Employee cannot report to themselves.',
            'fixed_shift_id.required_if' => 'Select a Fixed Shift when Shift Type is Fixed.',
            'biometric_identifier.unique' => 'This biometric identifier is already assigned to another Employee.',
            'contact.official_email.unique' => 'This official email is already in use by another Employee.',
            'bank.ifsc_code.regex' => 'Enter a valid IFSC code.',
        ];
    }
}
