<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchSwitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdministrator() === true;
    }

    public function rules(): array
    {
        return [
            'branch_selection' => ['required'],
        ];
    }
}
