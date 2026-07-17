<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;

class InactivateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Shift|null $shift */
        $shift = $this->route('shift');

        return $shift ? $this->user()?->can('inactivate', $shift) ?? false : false;
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
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
