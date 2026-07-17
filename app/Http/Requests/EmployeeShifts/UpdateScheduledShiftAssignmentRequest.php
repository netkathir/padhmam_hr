<?php

namespace App\Http\Requests\EmployeeShifts;

use App\Models\EmployeeShiftAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduledShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeShiftAssignment|null $assignment */
        $assignment = $this->route('assignment');

        return $assignment ? $this->user()?->can('editScheduled', $assignment) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignment_reason' => $this->filled('assignment_reason') ? trim((string) $this->input('assignment_reason')) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var EmployeeShiftAssignment $assignment */
        $assignment = $this->route('assignment');

        return [
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('branch_id', $assignment->branch_id)->where('status', 'active')],
            'effective_from' => ['required', 'date'],
            'effective_to' => [
                $assignment->isTemporary() ? 'required' : 'nullable',
                'date', 'after_or_equal:effective_from',
            ],
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
