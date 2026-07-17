<?php

namespace App\Http\Requests\Administration;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Organization::query()->sole()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_code' => $this->filled('organization_code')
                ? strtoupper(trim((string) $this->input('organization_code')))
                : $this->input('organization_code'),
            'pan_number' => $this->filled('pan_number')
                ? strtoupper(trim((string) $this->input('pan_number')))
                : $this->input('pan_number'),
            'tan_number' => $this->filled('tan_number')
                ? strtoupper(trim((string) $this->input('tan_number')))
                : $this->input('tan_number'),
            'gstin' => $this->filled('gstin')
                ? strtoupper(trim((string) $this->input('gstin')))
                : $this->input('gstin'),
        ]);
    }

    public function rules(): array
    {
        $organizationId = Organization::query()->value('id');

        return [
            'organization_code' => [
                'required', 'string', 'max:50',
                Rule::unique('organizations', 'organization_code')->ignore($organizationId),
            ],
            'legal_name' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:150'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'incorporation_date' => ['nullable', 'date', 'before_or_equal:today'],
            'financial_year_start_month' => ['required', 'integer', 'between:1,12'],

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

            'primary_phone' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'primary_email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            'pan_number' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'tan_number' => ['nullable', 'regex:/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/'],
            'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pf_registration_number' => ['nullable', 'string', 'max:50'],
            'esi_registration_number' => ['nullable', 'string', 'max:50'],
            'professional_tax_number' => ['nullable', 'string', 'max:50'],

            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'pan_number.regex' => 'Enter a valid PAN number (e.g. AAAAA9999A).',
            'tan_number.regex' => 'Enter a valid TAN number (e.g. AAAA99999A).',
            'gstin.regex' => 'Enter a valid GSTIN.',
            'postal_code.regex' => 'Enter a valid 6-digit Indian postal code.',
        ];
    }
}
