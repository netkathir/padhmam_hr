<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class InactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee ? $this->user()?->can('inactivate', $employee) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
