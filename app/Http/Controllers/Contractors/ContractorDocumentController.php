<?php

namespace App\Http\Controllers\Contractors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractors\UploadContractorDocumentRequest;
use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Services\Contractors\ContractorDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractorDocumentController extends Controller
{
    public function __construct(private readonly ContractorDocumentService $documentService)
    {
    }

    public function store(UploadContractorDocumentRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->documentService->upload($contractor, $request->safe()->except('file'), $request->file('file'), $request->user(), $request);

        return redirect()->route('contractors.master.show', $contractor)->with('status', 'Document uploaded successfully.');
    }

    public function download(Request $request, ContractorDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        $this->documentService->recordDownload($document, $request);

        return Storage::disk('public')->download($document->file_path, $document->original_filename ?? 'document');
    }

    public function inactivate(Request $request, ContractorDocument $document): RedirectResponse
    {
        $this->authorize('inactivate', $document);

        $this->documentService->inactivate($document, $request->user(), $request);

        return redirect()->route('contractors.master.show', $document->contractor)->with('status', 'Document inactivated successfully.');
    }
}
