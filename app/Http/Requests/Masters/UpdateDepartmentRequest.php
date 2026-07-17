<?php

namespace App\Http\Requests\Masters;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Department|null $department */
        $department = $this->route('department');

        return $department ? $this->user()?->can('update', $department) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'department_code' => $this->filled('department_code')
                ? strtoupper(trim((string) $this->input('department_code')))
                : $this->input('department_code'),
            'department_name' => $this->filled('department_name')
                ? trim((string) $this->input('department_name'))
                : $this->input('department_name'),
        ]);
    }

    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        return [
            'department_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('departments', 'department_code')->where('branch_id', $department->branch_id)->ignore($department->id),
            ],
            'department_name' => [
                'required', 'string', 'max:150',
                Rule::unique('departments', 'department_name')->where('branch_id', $department->branch_id)->ignore($department->id),
            ],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'department_code.regex' => 'The department code may only contain letters, numbers, hyphens, and underscores.',
            'department_code.unique' => 'This department code is already used in the active branch.',
            'department_name.unique' => 'This department name is already used in the active branch.',
        ];
    }
}
