<?php

namespace App\Http\Requests\EmployeeShifts;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeShiftAssignment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignment_reason' => $this->filled('assignment_reason') ? trim((string) $this->input('assignment_reason')) : null,
        ]);
    }

    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentBranchId();

        return [
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('branch_id', $branchId)->where('status', 'active')],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'assignment_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_id.exists' => 'Select an active Shift that belongs to the active Branch.',
            'effective_to.after_or_equal' => 'The effective-to date must not be earlier than the effective-from date.',
        ];
    }
}
