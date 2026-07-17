<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee ? $this->user()?->can('upload', [EmployeeDocument::class, $employee]) ?? false : false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('hrms.employee_document_max_kb', 5120);

        return [
            'document_type' => ['required', Rule::in(array_keys(config('hrms.employee_document_types')))],
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
            'file.max' => 'The document must not be larger than '.round((int) config('hrms.employee_document_max_kb', 5120) / 1024, 1).' MB.',
            'file.mimes' => 'The document must be a PDF, PNG, JPG, JPEG, or WebP file.',
            'expiry_date.after_or_equal' => 'The expiry date must not be earlier than the issued date.',
        ];
    }
}
