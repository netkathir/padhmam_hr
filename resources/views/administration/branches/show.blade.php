@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branches', 'url' => route('branches.index')], ['label' => $branch->branch_name]]" />

    <x-page-header :title="$branch->branch_name" :subtitle="$branch->branch_code">
        @can('update', $branch)
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('makeHeadOffice', $branch)
            @if(!$branch->isHeadOffice() && $branch->isActive())
                <form method="post" action="{{ route('branches.make-head-office', $branch) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-primary">Make Head Office</button>
                </form>
            @endif
        @endcan
        @can('activate', $branch)
            @if(!$branch->isActive())
                <form method="post" action="{{ route('branches.activate', $branch) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $branch)
            @if($branch->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateBranchModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$branch->status" />
            @if($branch->isHeadOffice())
                <span class="badge bg-primary">Head Office</span>
            @endif
            <span class="badge bg-light text-dark border">{{ $branch->typeLabel() }}</span>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Address</h2>
                <p class="mb-0">{{ $branch->formattedAddress() ?: '-' }}</p>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Contact</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Phone</dt>
                    <dd class="col-sm-7">{{ $branch->phone ?? '-' }}</dd>
                    <dt class="col-sm-5">Alternate Phone</dt>
                    <dd class="col-sm-7">{{ $branch->alternate_phone ?? '-' }}</dd>
                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7">{{ $branch->email ?? '-' }}</dd>
                    <dt class="col-sm-5">Contact Person</dt>
                    <dd class="col-sm-7">{{ $branch->contact_person_name ?? '-' }} {{ $branch->contact_person_phone ? '('.$branch->contact_person_phone.')' : '' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="page-surface p-4 h-100">
                <h2 class="h6 text-uppercase text-muted mb-3">Statutory Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">GSTIN</dt>
                    <dd class="col-sm-7">{{ $branch->gstin ?? '-' }}</dd>
                    <dt class="col-sm-5">PF Sub-code</dt>
                    <dd class="col-sm-7">{{ $branch->pf_sub_code ?? '-' }}</dd>
                    <dt class="col-sm-5">ESI Sub-code</dt>
                    <dd class="col-sm-7">{{ $branch->esi_sub_code ?? '-' }}</dd>
                    <dt class="col-sm-5">Professional Tax No.</dt>
                    <dd class="col-sm-7">{{ $branch->professional_tax_number ?? '-' }}</dd>
                    <dt class="col-sm-5">Establishment Code</dt>
                    <dd class="col-sm-7">{{ $branch->establishment_code ?? '-' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="page-surface p-4 h-100">
                <h2 class="h6 text-uppercase text-muted mb-3">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Active Users Assigned</dt>
                    <dd class="col-sm-7">{{ $activeUserCount }}</dd>
                    <dt class="col-sm-5">Timezone</dt>
                    <dd class="col-sm-7">{{ $branch->timezone }}</dd>
                    <dt class="col-sm-5">Display Order</dt>
                    <dd class="col-sm-7">{{ $branch->display_order }}</dd>
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $branch->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $branch->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $branch->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $branch->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @can('inactivate', $branch)
        <form id="inactivate-branch-form" method="post" action="{{ route('branches.inactivate', $branch) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateBranchModal"
            title="Inactivate Branch"
            :message="'Are you sure you want to inactivate '.$branch->branch_name.'? Users will not be reassigned automatically, and this branch will no longer be available for selection.'"
            form="inactivate-branch-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
