<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Employee documents are identity/compliance records, so they are stored on
 * the private `local` disk (never `public`) — see spec section 30.
 */
class EmployeeDocumentService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function upload(Employee $employee, array $data, UploadedFile $file, User $actor, Request $request): EmployeeDocument
    {
        $storedPath = $this->storeFile($employee, $file);

        $document = EmployeeDocument::create([
            ...$data,
            'employee_id' => $employee->id,
            'file_path' => $storedPath,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'active',
            'uploaded_by' => $actor->id,
        ]);

        $this->auditService->record(
            'employee_document_uploaded',
            'employee_document',
            $document,
            [],
            $this->auditableAttributes($document),
            $request,
        );

        return $document;
    }

    /**
     * Replaces a document's file in place (e.g. a clearer scan of the same
     * Aadhaar card) — the metadata row is kept so history/expiry tracking
     * is not lost, only the underlying file and its descriptive fields
     * change. The previous file is removed only after the new one is
     * safely stored.
     */
    public function replace(EmployeeDocument $document, array $data, UploadedFile $file, User $actor, Request $request): EmployeeDocument
    {
        $previousPath = $document->file_path;
        $old = $this->auditableAttributes($document);

        $storedPath = $this->storeFile($document->employee, $file);

        $document->update([
            ...$data,
            'file_path' => $storedPath,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $actor->id,
        ]);

        $this->auditService->record(
            'employee_document_replaced',
            'employee_document',
            $document,
            $old,
            $this->auditableAttributes($document->fresh()),
            $request,
        );

        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }

        return $document->fresh();
    }

    public function inactivate(EmployeeDocument $document, User $actor, Request $request): EmployeeDocument
    {
        $old = $this->auditableAttributes($document);

        $document->update(['status' => 'inactive']);

        $this->auditService->record(
            'employee_document_inactivated',
            'employee_document',
            $document,
            $old,
            $this->auditableAttributes($document->fresh()),
            $request,
        );

        return $document->fresh();
    }

    public function recordDownload(EmployeeDocument $document, Request $request): void
    {
        $this->auditService->record(
            'employee_document_downloaded',
            'employee_document',
            $document,
            [],
            ['document_type' => $document->document_type, 'employee_id' => $document->employee_id],
            $request,
        );
    }

    private function storeFile(Employee $employee, UploadedFile $file): string
    {
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs("employee-documents/{$employee->id}", $filename, 'local');
    }

    private function auditableAttributes(EmployeeDocument $document): array
    {
        return [
            'document_type' => $document->document_type,
            'document_number' => $document->document_number,
            'issued_date' => (string) $document->issued_date,
            'expiry_date' => (string) $document->expiry_date,
            'original_file_name' => $document->original_file_name,
            'status' => $document->status,
        ];
    }
}
