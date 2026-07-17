<?php

namespace App\Http\Requests\EmployeeNumbering;

use App\Models\EmployeeNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewEmployeeNumberRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->filled('rule_id')
            ? EmployeeNumberRule::query()->find($this->input('rule_id'))
            : null;

        return $this->user()?->can('preview', [EmployeeNumberRule::class, $rule]) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prefix' => $this->filled('prefix') ? strtoupper(trim((string) $this->input('prefix'))) : null,
            'employee_type_prefix' => $this->filled('employee_type_prefix') ? strtoupper(trim((string) $this->input('employee_type_prefix'))) : null,
            'separator' => $this->input('separator') === 'none' ? '' : (string) $this->input('separator', ''),
            'include_branch_code' => $this->boolean('include_branch_code'),
            'include_employee_type_prefix' => $this->boolean('include_employee_type_prefix'),
            'include_year' => $this->boolean('include_year'),
        ]);
    }

    public function rules(): array
    {
        return [
            'rule_id' => ['nullable', Rule::exists('employee_number_rules', 'id')],
            'employee_type_id' => ['required_without:rule_id', 'nullable', Rule::exists('employee_types', 'id')->where('status', 'active')],
            'prefix' => ['nullable', 'string', 'max:20'],
            'include_branch_code' => ['boolean'],
            'include_employee_type_prefix' => ['boolean'],
            'employee_type_prefix' => ['nullable', 'string', 'max:20'],
            'include_year' => ['boolean'],
            'year_format' => ['nullable', Rule::in([EmployeeNumberRule::YEAR_FORMAT_YY, EmployeeNumberRule::YEAR_FORMAT_YYYY])],
            'separator' => ['nullable', Rule::in(EmployeeNumberRule::SEPARATORS)],
            'serial_number_length' => ['required_without:rule_id', 'nullable', 'integer', 'min:3', 'max:10'],
            'starting_number' => ['required_without:rule_id', 'nullable', 'integer', 'min:1'],
            'reset_frequency' => ['required_without:rule_id', 'nullable', Rule::in([
                EmployeeNumberRule::RESET_NEVER,
                EmployeeNumberRule::RESET_YEARLY,
                EmployeeNumberRule::RESET_FINANCIAL_YEARLY,
            ])],
            'effective_from' => ['nullable', 'date'],
        ];
    }
}
