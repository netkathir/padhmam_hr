<?php

namespace App\Http\Requests\Administration;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return $role ? $this->user()?->can('update', $role) ?? false : false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
