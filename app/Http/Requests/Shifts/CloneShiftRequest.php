<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloneShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Shift|null $shift */
        $shift = $this->route('shift');

        return $shift ? $this->user()?->can('clone', $shift) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shift_code' => $this->filled('shift_code') ? strtoupper(trim((string) $this->input('shift_code'))) : $this->input('shift_code'),
            'shift_name' => $this->filled('shift_name') ? trim((string) $this->input('shift_name')) : $this->input('shift_name'),
        ]);
    }

    public function rules(): array
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');

        return [
            'shift_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('shifts', 'shift_code')->where('branch_id', $shift->branch_id),
            ],
            'shift_name' => [
                'required', 'string', 'max:150',
                Rule::unique('shifts', 'shift_name')->where('branch_id', $shift->branch_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_code.regex' => 'The shift code may only contain letters, numbers, hyphens, and underscores.',
            'shift_code.unique' => 'This shift code is already used within the active branch.',
            'shift_name.unique' => 'This shift name is already used within the active branch.',
        ];
    }
}
