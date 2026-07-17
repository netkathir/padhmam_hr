<?php

namespace App\Http\Requests\Masters;

use App\Models\Department;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
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
        $branchId = app(BranchContext::class)->currentBranchId();

        return [
            'department_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('departments', 'department_code')->where('branch_id', $branchId),
            ],
            'department_name' => [
                'required', 'string', 'max:150',
                Rule::unique('departments', 'department_name')->where('branch_id', $branchId),
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
