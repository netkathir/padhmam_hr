<?php

namespace App\Http\Requests\Contractors;

use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Services\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreContractorEngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ContractorBranchEngagement::class) ?? false;
    }

    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentBranchId();

        return [
            'contractor_id' => [
                'required', 'integer',
                Rule::exists('contractors', 'id')->where('status', 'active'),
                Rule::unique('contractor_branch_engagements', 'contractor_id')->where('branch_id', $branchId),
            ],
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'agreement_date' => ['nullable', 'date'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'maximum_labour_count' => ['nullable', 'integer', 'min:1'],
            'branch_labour_licence_number' => ['nullable', 'string', 'max:50'],
            'branch_licence_valid_from' => ['nullable', 'date'],
            'branch_licence_valid_to' => ['nullable', 'date', 'after_or_equal:branch_licence_valid_from'],
            'contact_person_name' => ['nullable', 'string', 'max:150'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('agreement_date') && $this->filled('contract_end_date')
                && $this->input('agreement_date') > $this->input('contract_end_date')) {
                $validator->errors()->add('agreement_date', 'The agreement date must not be later than the contract end date.');
            }

            if ($this->input('status') !== 'active' || $validator->errors()->isNotEmpty()) {
                return;
            }

            $contractor = Contractor::query()->find($this->input('contractor_id'));

            if (! $contractor) {
                return;
            }

            $engagement = new ContractorBranchEngagement($this->only([
                'contract_start_date', 'contract_end_date',
                'branch_labour_licence_number', 'branch_licence_valid_from', 'branch_licence_valid_to',
            ]));
            $engagement->setRelation('contractor', $contractor);
            $engagement->setRelation('branch', app(BranchContext::class)->currentBranch());

            if ($engagement->isContractExpired()) {
                $validator->errors()->add('contract_end_date', 'This Engagement cannot be activated because the contract period has already ended.');
            }

            if ($engagement->isLicenceExpired()) {
                $validator->errors()->add('branch_licence_valid_to', 'This Engagement cannot be activated because the applicable labour licence has expired.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'contractor_id.exists' => 'Select an active Contractor.',
            'contractor_id.unique' => 'This Contractor already has an Engagement for the active Branch.',
            'contract_end_date.after_or_equal' => 'The contract end date must not be earlier than the start date.',
            'branch_licence_valid_to.after_or_equal' => 'The licence valid-to date must not be earlier than the valid-from date.',
        ];
    }
}
