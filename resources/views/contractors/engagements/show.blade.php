@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branch Engagements', 'url' => route('contractors.engagements.index')], ['label' => $engagement->contractor->legal_name]]" />

    <x-page-header :title="$engagement->contractor->legal_name" :subtitle="'Engagement at '.$engagement->branch->branch_name">
        @can('update', $engagement)
            <a href="{{ route('contractors.engagements.edit', $engagement) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('activate', $engagement)
            @if(!$engagement->isActive())
                <form method="post" action="{{ route('contractors.engagements.activate', $engagement) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $engagement)
            @if($engagement->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateEngagementModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$engagement->status" />
            <x-validity-badge :label="$engagement->contractValidityStatus()" />
            @if($engagement->isLicenceExpired())
                <x-validity-badge label="Expired" class="ms-1" />
            @elseif($engagement->isLicenceExpiringSoon())
                <x-validity-badge label="Expiring Soon" class="ms-1" />
            @endif
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Contractor</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Contractor Code</dt>
                    <dd class="col-sm-7">{{ $engagement->contractor->contractor_code }}</dd>
                    <dt class="col-sm-5">Legal Name</dt>
                    <dd class="col-sm-7"><a href="{{ route('contractors.master.show', $engagement->contractor) }}">{{ $engagement->contractor->legal_name }}</a></dd>
                    <dt class="col-sm-5">Contractor Status</dt>
                    <dd class="col-sm-7"><x-status-badge :status="$engagement->contractor->status" /></dd>
                    <dt class="col-sm-5">Active Branch</dt>
                    <dd class="col-sm-7">{{ $engagement->branch->branch_name }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Agreement and Contract Period</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Agreement Number</dt>
                    <dd class="col-sm-7">{{ $engagement->agreement_number ?? '-' }}</dd>
                    <dt class="col-sm-5">Agreement Date</dt>
                    <dd class="col-sm-7">{{ $engagement->agreement_date?->format(config('hrms.date_format')) ?? '-' }}</dd>
                    <dt class="col-sm-5">Contract Start Date</dt>
                    <dd class="col-sm-7">{{ $engagement->contract_start_date?->format(config('hrms.date_format')) }}</dd>
                    <dt class="col-sm-5">Contract End Date</dt>
                    <dd class="col-sm-7">{{ $engagement->contract_end_date?->format(config('hrms.date_format')) ?? 'Open ended' }}</dd>
                    <dt class="col-sm-5">Maximum Labour Count</dt>
                    <dd class="col-sm-7">
                        {{ $engagement->maximum_labour_count ?? 'Not configured' }}
                        <div class="form-text mb-0">Active labour count checks will be enforced once Employee Registration is implemented.</div>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Effective Labour Licence</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Licence Number</dt>
                    <dd class="col-sm-7">{{ $engagement->effectiveLicenceNumber() ?? '-' }}</dd>
                    <dt class="col-sm-5">Valid From</dt>
                    <dd class="col-sm-7">{{ $engagement->effectiveLicenceValidFrom()?->format(config('hrms.date_format')) ?? '-' }}</dd>
                    <dt class="col-sm-5">Valid To</dt>
                    <dd class="col-sm-7">{{ $engagement->effectiveLicenceValidTo()?->format(config('hrms.date_format')) ?? '-' }}</dd>
                    <dt class="col-sm-5">Source</dt>
                    <dd class="col-sm-7">{{ $engagement->branch_labour_licence_number ? 'Branch-specific' : 'Contractor-level (fallback)' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Branch Contact</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Contact Person</dt>
                    <dd class="col-sm-7">{{ $engagement->contact_person_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Contact Phone</dt>
                    <dd class="col-sm-7">{{ $engagement->contact_person_phone ?? '-' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $engagement->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $engagement->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $engagement->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $engagement->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($engagement->remarks)
            <hr>
            <h2 class="h6 text-uppercase text-muted mb-2">Remarks</h2>
            <p class="mb-0">{{ $engagement->remarks }}</p>
        @endif
    </div>

    <div class="page-surface p-4">
        <h2 class="h6 text-uppercase text-muted mb-3">Documents</h2>
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Document Type</th>
                <th>Number</th>
                <th>Expiry</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($engagement->documents as $document)
                <tr>
                    <td>{{ $document->typeLabel() }}</td>
                    <td>{{ $document->document_number ?? '-' }}</td>
                    <td>{{ $document->expiry_date?->format(config('hrms.date_format')) ?? '-' }}</td>
                    <td><x-status-badge :status="$document->status" /></td>
                    <td class="text-end">
                        @can('view', $document)
                            <a href="{{ route('contractors.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><x-empty-state title="No documents yet" message="Documents for this engagement can be uploaded from the Contractor profile." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
    </div>

    @can('inactivate', $engagement)
        <form id="inactivate-engagement-form" method="post" action="{{ route('contractors.engagements.inactivate', $engagement) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateEngagementModal"
            title="Inactivate Branch Engagement"
            message="Are you sure you want to inactivate this Branch Engagement? Future Contract Labour employees will no longer be assignable through it."
            form="inactivate-engagement-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
