<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class ReactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee ? $this->user()?->can('reactivate', $employee) ?? false : false;
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
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
