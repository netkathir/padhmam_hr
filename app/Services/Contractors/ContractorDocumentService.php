<?php

namespace App\Services\Contractors;

use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractorDocumentService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function upload(Contractor $contractor, array $data, UploadedFile $file, User $actor, Request $request): ContractorDocument
    {
        $storedPath = $this->storeFile($contractor, $file);

        return DB::transaction(function () use ($contractor, $data, $file, $storedPath, $actor, $request): ContractorDocument {
            $document = ContractorDocument::create([
                ...$data,
                'contractor_id' => $contractor->id,
                'file_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'active',
                'uploaded_by' => $actor->id,
            ]);

            $this->auditService->record(
                'contractor_document_uploaded',
                'contractor_document',
                $document,
                [],
                $this->auditableAttributes($document),
                $request,
            );

            return $document;
        });
    }

    public function replace(ContractorDocument $document, UploadedFile $file, User $actor, Request $request): ContractorDocument
    {
        $previousPath = $document->file_path;
        $storedPath = $this->storeFile($document->contractor, $file);

        return DB::transaction(function () use ($document, $file, $storedPath, $previousPath, $actor, $request): ContractorDocument {
            $old = $this->auditableAttributes($document);

            $document->update([
                'file_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $actor->id,
            ]);

            $this->auditService->record(
                'contractor_document_replaced',
                'contractor_document',
                $document,
                $old,
                $this->auditableAttributes($document->fresh()),
                $request,
            );

            if ($previousPath) {
                Storage::disk('public')->delete($previousPath);
            }

            return $document->fresh();
        });
    }

    public function inactivate(ContractorDocument $document, User $actor, Request $request): ContractorDocument
    {
        return DB::transaction(function () use ($document, $actor, $request): ContractorDocument {
            $old = $this->auditableAttributes($document);

            $document->update(['status' => 'inactive']);

            $this->auditService->record(
                'contractor_document_inactivated',
                'contractor_document',
                $document,
                $old,
                $this->auditableAttributes($document->fresh()),
                $request,
            );

            return $document->fresh();
        });
    }

    public function recordDownload(ContractorDocument $document, Request $request): void
    {
        $this->auditService->record(
            'contractor_document_downloaded',
            'contractor_document',
            $document,
            [],
            ['document_type' => $document->document_type, 'contractor_id' => $document->contractor_id],
            $request,
        );
    }

    private function storeFile(Contractor $contractor, UploadedFile $file): string
    {
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs("contractor-documents/{$contractor->id}", $filename, 'public');
    }

    /**
     * File contents and storage paths are never written to audit logs, only
     * descriptive metadata.
     */
    private function auditableAttributes(ContractorDocument $document): array
    {
        return [
            'document_type' => $document->document_type,
            'document_number' => $document->document_number,
            'issued_date' => (string) $document->issued_date,
            'expiry_date' => (string) $document->expiry_date,
            'original_filename' => $document->original_filename,
            'status' => $document->status,
        ];
    }
}
