<?php

namespace App\Http\Requests\EmployeeNumbering;

use App\Models\EmployeeNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class ActivateEmployeeNumberRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EmployeeNumberRule|null $rule */
        $rule = $this->route('rule');

        return $rule ? $this->user()?->can('activate', $rule) ?? false : false;
    }

    public function rules(): array
    {
        return [];
    }
}
