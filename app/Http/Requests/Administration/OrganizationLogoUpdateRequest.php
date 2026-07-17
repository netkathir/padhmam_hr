<?php

namespace App\Http\Requests\Administration;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class OrganizationLogoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Organization::query()->sole()) ?? false;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:'.config('hrms.organization_logo_max_kb', 2048),
            ],
        ];
    }
}
