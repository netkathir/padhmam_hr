<?php

namespace App\Http\Requests\Administration;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return $branch ? $this->user()?->can('update', $branch) ?? false : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_code' => $this->filled('branch_code')
                ? strtoupper(trim((string) $this->input('branch_code')))
                : $this->input('branch_code'),
            'gstin' => $this->filled('gstin')
                ? strtoupper(trim((string) $this->input('gstin')))
                : $this->input('gstin'),
        ]);
    }

    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return [
            'branch_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('branches', 'branch_code')
                    ->where('organization_id', $branch?->organization_id)
                    ->ignore($branch?->id),
            ],
            'branch_name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'branch_type' => ['required', Rule::in(array_keys(Branch::TYPES))],

            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => [
                'required', 'string', 'max:20',
                Rule::when($this->input('country', 'India') === 'India', ['regex:/^[1-9][0-9]{5}$/']),
            ],

            'phone' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_person_name' => ['nullable', 'string', 'max:150'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],

            'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pf_sub_code' => ['nullable', 'string', 'max:50'],
            'esi_sub_code' => ['nullable', 'string', 'max:50'],
            'professional_tax_number' => ['nullable', 'string', 'max:50'],
            'establishment_code' => ['nullable', 'string', 'max:50'],

            'timezone' => ['required', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_code.regex' => 'The branch code may only contain letters, numbers, hyphens, and underscores.',
            'gstin.regex' => 'Enter a valid GSTIN.',
            'postal_code.regex' => 'Enter a valid 6-digit Indian postal code.',
        ];
    }
}
