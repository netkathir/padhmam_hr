<?php

namespace App\Http\Requests\EmployeeShifts;

use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmployeeShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('change', \App\Models\EmployeeShiftAssignment::class) ?? false;
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
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_id.exists' => 'Select an active Fixed Shift that belongs to the active Branch.',
            'reason.required' => 'A reason is required for a Shift change.',
        ];
    }
}
