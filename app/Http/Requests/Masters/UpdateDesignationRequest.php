<?php

namespace App\Http\Requests\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Designation|null $designation */
        $designation = $this->route('designation');

        return $designation ? $this->user()?->can('update', $designation) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $scope = $this->input('scope');
        $departmentId = $this->input('department_id');
        $sectionId = $this->input('section_id');

        if ($scope === Designation::SCOPE_BRANCH) {
            $departmentId = null;
            $sectionId = null;
        } elseif ($scope === Designation::SCOPE_DEPARTMENT) {
            $sectionId = null;
        }

        $this->merge([
            'designation_code' => $this->filled('designation_code')
                ? strtoupper(trim((string) $this->input('designation_code')))
                : $this->input('designation_code'),
            'designation_name' => $this->filled('designation_name')
                ? trim((string) $this->input('designation_name'))
                : $this->input('designation_name'),
            'department_id' => $departmentId,
            'section_id' => $sectionId,
        ]);
    }

    public function rules(): array
    {
        /** @var Designation $designation */
        $designation = $this->route('designation');
        $departmentId = $this->input('department_id');

        return [
            'scope' => ['required', Rule::in([Designation::SCOPE_BRANCH, Designation::SCOPE_DEPARTMENT, Designation::SCOPE_SECTION])],
            'department_id' => [
                Rule::requiredIf(in_array($this->input('scope'), [Designation::SCOPE_DEPARTMENT, Designation::SCOPE_SECTION], true)),
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('branch_id', $designation->branch_id),
            ],
            'section_id' => [
                Rule::requiredIf($this->input('scope') === Designation::SCOPE_SECTION),
                'nullable', 'integer',
                Rule::exists('sections', 'id')->where('branch_id', $designation->branch_id)->where('department_id', $departmentId),
            ],
            'designation_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('designations', 'designation_code')->where('branch_id', $designation->branch_id)->ignore($designation->id),
            ],
            'designation_name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hierarchy_level' => ['nullable', 'integer', 'min:1'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'active') {
                return;
            }

            if ($this->filled('department_id') && ! $validator->errors()->has('department_id')) {
                $department = Department::query()->find($this->input('department_id'));

                if ($department && ! $department->isActive()) {
                    $validator->errors()->add('department_id', 'This Designation cannot be active because its Department is inactive.');
                }
            }

            if ($this->filled('section_id') && ! $validator->errors()->has('section_id')) {
                $section = Section::query()->find($this->input('section_id'));

                if ($section && ! $section->isActive()) {
                    $validator->errors()->add('section_id', 'This Designation cannot be active because its Section is inactive.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Select a Department for this scope.',
            'department_id.exists' => 'Select a Department that belongs to the active branch.',
            'section_id.required' => 'Select a Section for this scope.',
            'section_id.exists' => 'Select a Section that belongs to the selected Department.',
            'designation_code.regex' => 'The designation code may only contain letters, numbers, hyphens, and underscores.',
            'designation_code.unique' => 'This designation code is already used in the active branch.',
        ];
    }
}
