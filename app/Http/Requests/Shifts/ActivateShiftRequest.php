<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;

class ActivateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Shift|null $shift */
        $shift = $this->route('shift');

        return $shift ? $this->user()?->can('activate', $shift) ?? false : false;
    }

    public function rules(): array
    {
        return [];
    }
}
