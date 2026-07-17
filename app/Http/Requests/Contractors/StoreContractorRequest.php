<?php

namespace App\Http\Requests\Contractors;

use App\Models\Contractor;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Contractor::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contractor_code' => $this->filled('contractor_code')
                ? strtoupper(trim((string) $this->input('contractor_code')))
                : $this->input('contractor_code'),
            'legal_name' => $this->filled('legal_name') ? trim((string) $this->input('legal_name')) : $this->input('legal_name'),
            'trade_name' => $this->filled('trade_name') ? trim((string) $this->input('trade_name')) : $this->input('trade_name'),
            'pan_number' => $this->filled('pan_number') ? strtoupper(trim((string) $this->input('pan_number'))) : $this->input('pan_number'),
            'gstin' => $this->filled('gstin') ? strtoupper(trim((string) $this->input('gstin'))) : $this->input('gstin'),
        ]);
    }

    public function rules(): array
    {
        $organizationId = Organization::query()->value('id');

        return [
            'contractor_code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('contractors', 'contractor_code')->where('organization_id', $organizationId),
            ],
            'legal_name' => [
                'required', 'string', 'max:200',
                Rule::unique('contractors', 'legal_name')->where('organization_id', $organizationId),
            ],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'contractor_type' => ['nullable', Rule::in(array_keys(Contractor::TYPES))],
            'contact_person_name' => ['required', 'string', 'max:150'],
            'primary_phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'primary_email' => ['nullable', 'email', 'max:150'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'pan_number' => [
                'nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
                Rule::unique('contractors', 'pan_number')->where('organization_id', $organizationId),
            ],
            'gstin' => [
                'nullable', 'string', 'max:20',
                Rule::unique('contractors', 'gstin')->where('organization_id', $organizationId),
            ],
            'pf_registration_number' => ['nullable', 'string', 'max:50'],
            'esi_registration_number' => ['nullable', 'string', 'max:50'],
            'labour_licence_number' => ['nullable', 'string', 'max:50'],
            'labour_licence_valid_from' => ['nullable', 'date'],
            'labour_licence_valid_to' => ['nullable', 'date', 'after_or_equal:labour_licence_valid_from'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'contractor_code.regex' => 'The contractor code may only contain letters, numbers, hyphens, and underscores.',
            'contractor_code.unique' => 'This contractor code is already used within the organization.',
            'legal_name.unique' => 'A contractor with this legal name already exists within the organization.',
            'pan_number.regex' => 'Enter a valid PAN in the format AAAAA9999A.',
            'pan_number.unique' => 'This PAN is already registered to another contractor.',
            'gstin.unique' => 'This GSTIN is already registered to another contractor.',
            'labour_licence_valid_to.after_or_equal' => 'The licence valid-to date must not be earlier than the valid-from date.',
        ];
    }
}
