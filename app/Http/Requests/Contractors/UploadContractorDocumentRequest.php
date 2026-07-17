<?php

namespace App\Http\Requests\Contractors;

use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Models\ContractorDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadContractorDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Contractor|null $contractor */
        $contractor = $this->route('contractor');

        if (! $contractor) {
            return false;
        }

        $engagement = null;

        if ($this->filled('contractor_branch_engagement_id')) {
            $engagement = ContractorBranchEngagement::withoutGlobalScopes()
                ->where('contractor_id', $contractor->id)
                ->find($this->input('contractor_branch_engagement_id'));
        }

        return $this->user()?->can('upload', [ContractorDocument::class, $contractor, $engagement]) ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('hrms.contractor_document_max_kb', 5120);

        return [
            'contractor_branch_engagement_id' => [
                'nullable', 'integer',
                Rule::exists('contractor_branch_engagements', 'id')->where('contractor_id', $this->route('contractor')?->id),
            ],
            'document_type' => ['required', Rule::in(array_keys(config('hrms.contractor_document_types')))],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'file' => [
                'required', 'file', 'max:'.$maxKb,
                'mimes:pdf,png,jpg,jpeg,webp',
                'mimetypes:application/pdf,image/png,image/jpeg,image/webp',
            ],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The document must not be larger than '.round((int) config('hrms.contractor_document_max_kb', 5120) / 1024, 1).' MB.',
            'file.mimes' => 'The document must be a PDF, PNG, JPG, JPEG, or WebP file.',
            'expiry_date.after_or_equal' => 'The expiry date must not be earlier than the issued date.',
        ];
    }
}
