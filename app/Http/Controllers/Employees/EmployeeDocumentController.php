<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\UploadEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\Employees\EmployeeDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function __construct(private readonly EmployeeDocumentService $documentService)
    {
    }

    public function store(UploadEmployeeDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $this->documentService->upload($employee, $request->safe()->except('file'), $request->file('file'), $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Document uploaded successfully.');
    }

    public function download(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('download', $document);

        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        $this->documentService->recordDownload($document, $request);

        return Storage::disk('local')->download($document->file_path, $document->original_file_name ?? 'document');
    }

    public function replace(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('upload', [EmployeeDocument::class, $document->employee]);

        $maxKb = (int) config('hrms.employee_document_max_kb', 5120);

        $data = $request->validate([
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'file' => [
                'required', 'file', 'max:'.$maxKb,
                'mimes:pdf,png,jpg,jpeg,webp',
                'mimetypes:application/pdf,image/png,image/jpeg,image/webp',
            ],
        ]);

        $file = $data['file'];
        unset($data['file']);

        $this->documentService->replace($document, $data, $file, $request->user(), $request);

        return redirect()->route('employees.show', $document->employee)->with('status', 'Document replaced successfully.');
    }

    public function inactivate(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('inactivate', $document);

        $this->documentService->inactivate($document, $request->user(), $request);

        return redirect()->route('employees.show', $document->employee)->with('status', 'Document inactivated successfully.');
    }
}
