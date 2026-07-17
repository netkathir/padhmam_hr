<?php

namespace App\Http\Requests\EmployeeShifts;

use App\Models\EmployeeShiftAssignment;
use Illuminate\Foundation\Http\FormRequest;

class CancelEmployeeShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeShiftAssignment|null $assignment */
        $assignment = $this->route('assignment');

        return $assignment ? $this->user()?->can('cancel', $assignment) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancellation_reason' => $this->filled('cancellation_reason') ? trim((string) $this->input('cancellation_reason')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'A cancellation reason is required.',
        ];
    }
}
