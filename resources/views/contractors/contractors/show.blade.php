@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Contractor Master', 'url' => route('contractors.master.index')], ['label' => $contractor->legal_name]]" />

    <x-page-header :title="$contractor->legal_name" :subtitle="$contractor->contractor_code">
        @can('update', $contractor)
            <a href="{{ route('contractors.master.edit', $contractor) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('activate', $contractor)
            @if(!$contractor->isActive())
                <form method="post" action="{{ route('contractors.master.activate', $contractor) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $contractor)
            @if($contractor->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateContractorModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$contractor->status" />
            <x-validity-badge :label="$contractor->licenceValidityLabel()" />
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Legal Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Trade Name</dt>
                    <dd class="col-sm-7">{{ $contractor->trade_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Contractor Type</dt>
                    <dd class="col-sm-7">{{ $contractor->contractor_type ? $contractor->typeLabel() : '-' }}</dd>
                    @can('update', $contractor)
                        <dt class="col-sm-5">PAN</dt>
                        <dd class="col-sm-7">{{ $contractor->pan_number ?? '-' }}</dd>
                        <dt class="col-sm-5">GSTIN</dt>
                        <dd class="col-sm-7">{{ $contractor->gstin ?? '-' }}</dd>
                        <dt class="col-sm-5">PF Registration</dt>
                        <dd class="col-sm-7">{{ $contractor->pf_registration_number ?? '-' }}</dd>
                        <dt class="col-sm-5">ESI Registration</dt>
                        <dd class="col-sm-7">{{ $contractor->esi_registration_number ?? '-' }}</dd>
                    @else
                        <dt class="col-sm-5">PAN</dt>
                        <dd class="col-sm-7">{{ \App\Models\Contractor::maskStatutoryNumber($contractor->pan_number) ?? '-' }}</dd>
                        <dt class="col-sm-5">GSTIN</dt>
                        <dd class="col-sm-7">{{ \App\Models\Contractor::maskStatutoryNumber($contractor->gstin) ?? '-' }}</dd>
                    @endcan
                    <dt class="col-sm-5">Labour Licence Number</dt>
                    <dd class="col-sm-7">{{ $contractor->labour_licence_number ?? '-' }}</dd>
                    <dt class="col-sm-5">Licence Valid From</dt>
                    <dd class="col-sm-7">{{ $contractor->labour_licence_valid_from?->format(config('hrms.date_format')) ?? '-' }}</dd>
                    <dt class="col-sm-5">Licence Valid To</dt>
                    <dd class="col-sm-7">{{ $contractor->labour_licence_valid_to?->format(config('hrms.date_format')) ?? '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Contact and Address</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Contact Person</dt>
                    <dd class="col-sm-7">{{ $contractor->contact_person_name }}</dd>
                    <dt class="col-sm-5">Primary Phone</dt>
                    <dd class="col-sm-7">{{ $contractor->primary_phone }}</dd>
                    <dt class="col-sm-5">Alternate Phone</dt>
                    <dd class="col-sm-7">{{ $contractor->alternate_phone ?? '-' }}</dd>
                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7">{{ $contractor->primary_email ?? '-' }}</dd>
                    <dt class="col-sm-5">Address</dt>
                    <dd class="col-sm-7">{{ collect([$contractor->address_line_1, $contractor->address_line_2, $contractor->city, $contractor->district, $contractor->state, $contractor->postal_code, $contractor->country])->filter()->implode(', ') }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $contractor->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $contractor->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $contractor->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $contractor->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($contractor->description)
            <hr>
            <h2 class="h6 text-uppercase text-muted mb-2">Description</h2>
            <p class="mb-0">{{ $contractor->description }}</p>
        @endif
    </div>

    <div class="page-surface p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 text-uppercase text-muted mb-0">Branch Engagements</h2>
            @can('create', \App\Models\ContractorBranchEngagement::class)
                <a href="{{ route('contractors.engagements.create') }}" class="btn btn-sm btn-primary">New Engagement</a>
            @endcan
        </div>
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Branch</th>
                <th>Agreement Number</th>
                <th>Contract Period</th>
                <th>Max Labour</th>
                <th>Contract Validity</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($engagements as $engagement)
                <tr>
                    <td>{{ $engagement->branch?->branch_name }}</td>
                    <td>{{ $engagement->agreement_number ?? '-' }}</td>
                    <td>
                        {{ $engagement->contract_start_date?->format(config('hrms.date_format')) }}
                        &ndash;
                        {{ $engagement->contract_end_date?->format(config('hrms.date_format')) ?? 'Open' }}
                    </td>
                    <td>{{ $engagement->maximum_labour_count ?? 'Not configured' }}</td>
                    <td><x-validity-badge :label="$engagement->contractValidityStatus()" /></td>
                    <td><x-status-badge :status="$engagement->status" /></td>
                    <td class="text-end">
                        @can('view', $engagement)
                            <a href="{{ route('contractors.engagements.show', $engagement) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7"><x-empty-state title="No branch engagements yet" message="This contractor has not been engaged at any branch." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
    </div>

    <div class="page-surface p-4">
        <h2 class="h6 text-uppercase text-muted mb-3">Compliance Documents</h2>

        <x-data-table class="table mb-3">
            <thead>
            <tr>
                <th>Document Type</th>
                <th>Number</th>
                <th>Branch Engagement</th>
                <th>Expiry</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $document)
                <tr>
                    <td>{{ $document->typeLabel() }}</td>
                    <td>{{ $document->document_number ?? '-' }}</td>
                    <td>{{ $document->engagement?->branch?->branch_name ?? 'Organization-level' }}</td>
                    <td>
                        {{ $document->expiry_date?->format(config('hrms.date_format')) ?? '-' }}
                        @if($document->isExpired())
                            <x-validity-badge label="Expired" />
                        @elseif($document->isExpiringSoon())
                            <x-validity-badge label="Expiring Soon" />
                        @endif
                    </td>
                    <td><x-status-badge :status="$document->status" /></td>
                    <td class="text-end">
                        @can('view', $document)
                            <a href="{{ route('contractors.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                        @endcan
                        @can('inactivate', $document)
                            @if($document->isActive())
                                <form method="post" action="{{ route('contractors.documents.inactivate', $document) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Inactivate</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><x-empty-state title="No documents yet" message="Upload compliance documents for this contractor." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>

        @can('upload', [\App\Models\ContractorDocument::class, $contractor])
            <hr>
            <h3 class="h6 mb-3">Upload Document</h3>
            <form method="post" action="{{ route('contractors.documents.store', $contractor) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-form.select name="document_type" label="Document Type" :options="$documentTypes" required />
                    </div>
                    <div class="col-md-3">
                        <x-form.select name="contractor_branch_engagement_id" label="Branch Engagement" :options="$engagements->pluck('branch.branch_name', 'id')">
                            <option value="">Organization-level (no engagement)</option>
                        </x-form.select>
                    </div>
                    <div class="col-md-3"><x-form.input name="document_number" label="Document Number" /></div>
                    <div class="col-md-3"><x-form.input type="date" name="issued_date" label="Issued Date" /></div>
                    <div class="col-md-3"><x-form.input type="date" name="expiry_date" label="Expiry Date" /></div>
                    <div class="col-md-5">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required accept=".pdf,.png,.jpg,.jpeg,.webp">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12"><x-form.textarea name="remarks" label="Remarks" rows="2" /></div>
                </div>
                <div class="mt-3">
                    <x-submit-button label="Upload Document" />
                </div>
            </form>
        @endcan
    </div>

    @can('inactivate', $contractor)
        <form id="inactivate-contractor-form" method="post" action="{{ route('contractors.master.inactivate', $contractor) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateContractorModal"
            title="Inactivate Contractor"
            :message="'Are you sure you want to inactivate '.$contractor->legal_name.'? This contractor will no longer be available for selection.'"
            form="inactivate-contractor-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
