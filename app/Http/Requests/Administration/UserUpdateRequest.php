<?php

namespace App\Http\Requests\Administration;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return $user ? $this->user()?->can('update', $user) ?? false : false;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user()?->isSuperAdministrator()) {
                return;
            }

            $roleIds = (array) $this->input('roles', []);

            if (Role::query()->whereIn('id', $roleIds)->get()->contains(fn (Role $role): bool => $role->isSuperAdministrator())) {
                $validator->errors()->add('roles', 'Only a Super Administrator can assign the Super Administrator role.');
            }
        });
    }
}
