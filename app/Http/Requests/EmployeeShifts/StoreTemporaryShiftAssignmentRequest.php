<?php

namespace App\Http\Requests\EmployeeShifts;

use App\Models\EmployeeShiftAssignment;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemporaryShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('temporary', EmployeeShiftAssignment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
        ]);
    }

    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentBranchId();

        return [
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('branch_id', $branchId)->where('status', 'active')],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_id.exists' => 'Select an active Shift that belongs to the active Branch.',
            'effective_to.required' => 'An end date is mandatory for a Temporary Shift assignment.',
            'effective_to.after_or_equal' => 'The effective-to date must not be earlier than the effective-from date.',
            'reason.required' => 'A reason is required for a Temporary Shift assignment.',
        ];
    }
}
