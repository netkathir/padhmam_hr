<?php

namespace App\Http\Requests\Masters;

use App\Models\Department;
use App\Models\Section;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Section::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'section_code' => $this->filled('section_code')
                ? strtoupper(trim((string) $this->input('section_code')))
                : $this->input('section_code'),
            'section_name' => $this->filled('section_name')
                ? trim((string) $this->input('section_name'))
                : $this->input('section_name'),
        ]);
    }

    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentBranchId();
        $departmentId = $this->input('department_id');

        return [
            'department_id' => [
                'required', 'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId),
            ],
            'section_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('sections', 'section_code')->where('department_id', $departmentId),
            ],
            'section_name' => [
                'required', 'string', 'max:150',
                Rule::unique('sections', 'section_name')->where('department_id', $departmentId),
            ],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('department_id')) {
                return;
            }

            $department = Department::query()->find($this->input('department_id'));

            if ($department && $this->input('status') === 'active' && ! $department->isActive()) {
                $validator->errors()->add('department_id', 'This Section cannot be activated because its Department is inactive.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'Select a Department that belongs to the active branch.',
            'section_code.regex' => 'The section code may only contain letters, numbers, hyphens, and underscores.',
            'section_code.unique' => 'This section code is already used within the selected Department.',
            'section_name.unique' => 'This section name is already used within the selected Department.',
        ];
    }
}
