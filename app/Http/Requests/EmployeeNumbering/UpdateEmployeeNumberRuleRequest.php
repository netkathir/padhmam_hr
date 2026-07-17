<?php

namespace App\Http\Requests\EmployeeNumbering;

use App\Models\EmployeeNumberRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeNumberRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeNumberRule|null $rule */
        $rule = $this->route('rule');

        return $rule ? $this->user()?->can('update', $rule) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        /** @var EmployeeNumberRule $rule */
        $rule = $this->route('rule');

        $merge = [
            'rule_name' => $this->filled('rule_name') ? trim((string) $this->input('rule_name')) : $this->input('rule_name'),
        ];

        if (! $rule->hasIssuedNumbers()) {
            $merge['prefix'] = $this->filled('prefix') ? strtoupper(trim((string) $this->input('prefix'))) : null;
            $merge['employee_type_prefix'] = $this->filled('employee_type_prefix') ? strtoupper(trim((string) $this->input('employee_type_prefix'))) : null;
            $merge['separator'] = $this->input('separator') === 'none' ? '' : (string) $this->input('separator', '');
            $merge['include_branch_code'] = $this->boolean('include_branch_code');
            $merge['include_employee_type_prefix'] = $this->boolean('include_employee_type_prefix');
            $merge['include_year'] = $this->boolean('include_year');
            $merge['is_default'] = $this->boolean('is_default', true);
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        /** @var EmployeeNumberRule $rule */
        $rule = $this->route('rule');

        if ($rule->hasIssuedNumbers()) {
            return [
                'rule_name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:2000'],
                'effective_to' => [
                    'nullable', 'date',
                    function (string $attribute, mixed $value, \Closure $fail) use ($rule): void {
                        if ($value && $rule->effective_from && Carbon::parse($value)->lt($rule->effective_from)) {
                            $fail('The effective-to date must not be earlier than the effective-from date.');
                        }
                    },
                ],
            ];
        }

        return [
            'rule_name' => ['required', 'string', 'max:150'],
            'employee_type_id' => ['required', Rule::exists('employee_types', 'id')->where('status', 'active')],
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]*$/'],
            'include_branch_code' => ['required', 'boolean'],
            'include_employee_type_prefix' => ['required', 'boolean'],
            'employee_type_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]*$/'],
            'include_year' => ['required', 'boolean'],
            'year_format' => ['nullable', Rule::in([EmployeeNumberRule::YEAR_FORMAT_YY, EmployeeNumberRule::YEAR_FORMAT_YYYY])],
            'separator' => ['nullable', Rule::in(EmployeeNumberRule::SEPARATORS)],
            'serial_number_length' => ['required', 'integer', 'min:3', 'max:10'],
            'starting_number' => ['required', 'integer', 'min:1'],
            'reset_frequency' => ['required', Rule::in([
                EmployeeNumberRule::RESET_NEVER,
                EmployeeNumberRule::RESET_YEARLY,
                EmployeeNumberRule::RESET_FINANCIAL_YEARLY,
            ])],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_default' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var EmployeeNumberRule $rule */
        $rule = $this->route('rule');

        if ($rule->hasIssuedNumbers()) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if ($this->boolean('include_year') && ! $this->filled('year_format')) {
                $validator->errors()->add('year_format', 'Select a year format when the year component is included.');
            }

            $prefix = (string) $this->input('prefix', '');
            $separator = (string) $this->input('separator', '');

            if ($prefix !== '' && $separator !== '' && str_contains($prefix, $separator)) {
                $validator->errors()->add('prefix', 'The prefix must not contain the selected separator character.');
            }

            $starting = (int) $this->input('starting_number', 0);
            $length = (int) $this->input('serial_number_length', 0);

            if ($starting > 0 && $length > 0 && strlen((string) $starting) > $length) {
                $validator->errors()->add('starting_number', 'The starting number does not fit within the configured serial number length.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_type_id.exists' => 'Select an active Employee Type.',
            'prefix.regex' => 'The prefix may only contain letters, numbers, hyphens, and underscores.',
            'employee_type_prefix.regex' => 'The Employee Type prefix may only contain letters, numbers, hyphens, and underscores.',
            'effective_to.after_or_equal' => 'The effective-to date must not be earlier than the effective-from date.',
        ];
    }
}
