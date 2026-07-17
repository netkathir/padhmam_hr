<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        if ($employee) {
            return $this->user()?->can('update', $employee) ?? false;
        }

        return $this->user()?->can('create', Employee::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->filled('first_name') ? trim((string) $this->input('first_name')) : $this->input('first_name'),
            'middle_name' => $this->filled('middle_name') ? trim((string) $this->input('middle_name')) : null,
            'last_name' => $this->filled('last_name') ? trim((string) $this->input('last_name')) : null,
            'biometric_identifier' => $this->filled('biometric_identifier') ? trim((string) $this->input('biometric_identifier')) : null,
            'attendance_applicable' => $this->has('attendance_applicable') ? $this->boolean('attendance_applicable') : null,
            'leave_applicable' => $this->has('leave_applicable') ? $this->boolean('leave_applicable') : null,
            'payroll_applicable' => $this->has('payroll_applicable') ? $this->boolean('payroll_applicable') : null,
            'overtime_applicable' => $this->has('overtime_applicable') ? $this->boolean('overtime_applicable') : null,
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
        /** @var Employee|null $employee */
        $employee = $this->route('employee');
        $branchId = $employee?->branch_id ?? app(BranchContext::class)->currentBranchId();
        $nameRegex = "/^[\\p{L}'\\-\\s]+$/u";

        return [
            'employee_type_id' => ['required', Rule::exists('employee_types', 'id')->where('status', 'active')],

            'first_name' => ['required', 'string', 'max:100', 'regex:'.$nameRegex],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:'.$nameRegex],
            'last_name' => ['nullable', 'string', 'max:100', 'regex:'.$nameRegex],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(array_keys(Employee::GENDERS))],
            'marital_status' => ['nullable', Rule::in(array_keys(Employee::MARITAL_STATUSES))],
            'blood_group' => ['nullable', Rule::in(Employee::BLOOD_GROUPS)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.config('hrms.employee_photo_max_kb')],

            'date_of_joining' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'probation_applicable' => ['boolean'],
            'probation_period_days' => ['nullable', 'integer', 'min:1'],
            'probation_end_date' => ['nullable', 'date'],

            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('branch_id', $branchId)],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('branch_id', $branchId)],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('branch_id', $branchId)],
            'reporting_manager_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('branch_id', $branchId)->where('status', Employee::STATUS_ACTIVE),
            ],

            'shift_type' => ['nullable', Rule::in([Employee::SHIFT_TYPE_FIXED, Employee::SHIFT_TYPE_ROTATIONAL])],
            'fixed_shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('branch_id', $branchId)],
            'shift_type_override_reason' => ['nullable', 'string', 'max:500'],

            'contractor_id' => ['nullable', Rule::exists('contractors', 'id')],
            'contractor_branch_engagement_id' => ['nullable', Rule::exists('contractor_branch_engagements', 'id')->where('branch_id', $branchId)],

            'biometric_identifier' => ['nullable', 'string', 'max:100', Rule::unique('employees', 'biometric_identifier')->ignore($employee?->id)],

            'attendance_applicable' => ['nullable', 'boolean'],
            'leave_applicable' => ['nullable', 'boolean'],
            'payroll_applicable' => ['nullable', 'boolean'],
            'overtime_applicable' => ['nullable', 'boolean'],
            'applicability_override_reason' => ['nullable', 'string', 'max:500'],

            'contact.personal_mobile' => ['nullable', 'string', 'max:20'],
            'contact.alternate_mobile' => ['nullable', 'string', 'max:20'],
            'contact.personal_email' => ['nullable', 'email', 'max:150'],
            'contact.official_email' => ['nullable', 'email', 'max:150', Rule::unique('employee_contacts', 'official_email')->ignore($employee?->contact?->id)],

            'addresses.current.address_line_1' => ['nullable', 'string', 'max:255'],
            'addresses.current.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.current.city' => ['nullable', 'string', 'max:100'],
            'addresses.current.district' => ['nullable', 'string', 'max:100'],
            'addresses.current.state' => ['nullable', 'string', 'max:100'],
            'addresses.current.country' => ['nullable', 'string', 'max:100'],
            'addresses.current.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.permanent.is_same_as_current' => ['boolean'],
            'addresses.permanent.address_line_1' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.permanent.city' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.district' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.state' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.country' => ['nullable', 'string', 'max:100'],
            'addresses.permanent.postal_code' => ['nullable', 'string', 'max:20'],

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
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('date_of_birth') && ! $this->filled('date_of_joining')) {
                $validator->errors()->add('date_of_birth', 'Enter at least the Date of Birth or the Date of Joining to save a Draft.');
            }

            if ($this->filled('section_id') && $this->filled('department_id')) {
                $section = \App\Models\Section::query()->find($this->input('section_id'));

                if ($section && $section->department_id !== (int) $this->input('department_id')) {
                    $validator->errors()->add('section_id', 'The selected Section does not belong to the selected Department.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_type_id.exists' => 'Select an active Employee Type.',
            'first_name.regex' => 'The first name may only contain letters, spaces, apostrophes, and hyphens.',
            'last_name.regex' => 'The last name may only contain letters, spaces, apostrophes, and hyphens.',
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'biometric_identifier.unique' => 'This biometric identifier is already assigned to another Employee.',
            'contact.official_email.unique' => 'This official email is already in use by another Employee.',
            'bank.ifsc_code.regex' => 'Enter a valid IFSC code.',
        ];
    }
}
