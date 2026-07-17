@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Organization Profile']]" />

    <x-page-header title="Organization Profile" subtitle="Company-level profile shared across all branches.">
        @if($canEdit)
            <a href="{{ route('organization.edit') }}" class="btn btn-primary">Edit Profile</a>
        @endif
    </x-page-header>

    <div class="page-surface p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-2 text-center">
                <img
                    src="{{ $organization->logoUrl() ?? 'https://placehold.co/160x160?text=Logo' }}"
                    alt="Organization logo"
                    class="img-fluid rounded border p-2 mb-2"
                    style="max-height: 160px"
                >
                @if($canEdit)
                    <form method="post" action="{{ route('organization.logo.update') }}" enctype="multipart/form-data" class="mt-2">
                        @csrf
                        <input type="file" name="logo" class="form-control form-control-sm mb-2" accept=".png,.jpg,.jpeg,.webp" required>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Upload Logo</button>
                    </form>
                @endif
            </div>
            <div class="col-md-10">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Organization Code</dt>
                    <dd class="col-sm-9">{{ $organization->organization_code }}</dd>

                    <dt class="col-sm-3">Legal Name</dt>
                    <dd class="col-sm-9">{{ $organization->legal_name }}</dd>

                    <dt class="col-sm-3">Display Name</dt>
                    <dd class="col-sm-9">{{ $organization->display_name }}</dd>

                    <dt class="col-sm-3">Business Type</dt>
                    <dd class="col-sm-9">{{ $organization->business_type ?? '-' }}</dd>

                    <dt class="col-sm-3">Incorporation Date</dt>
                    <dd class="col-sm-9">{{ $organization->incorporation_date?->format(config('hrms.date_format')) ?? '-' }}</dd>

                    <dt class="col-sm-3">Financial Year Start</dt>
                    <dd class="col-sm-9">{{ \Carbon\Carbon::create()->month($organization->financial_year_start_month)->format('F') }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9"><x-status-badge :status="$organization->status" /></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="page-surface p-4 h-100">
                <h2 class="h6 text-uppercase text-muted mb-3">Registered Address</h2>
                <p class="mb-0">
                    {{ $organization->address_line_1 }}<br>
                    @if($organization->address_line_2){{ $organization->address_line_2 }}<br>@endif
                    {{ collect([$organization->city, $organization->district, $organization->state])->filter()->implode(', ') }}<br>
                    {{ $organization->country }} - {{ $organization->postal_code }}
                </p>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="page-surface p-4 h-100">
                <h2 class="h6 text-uppercase text-muted mb-3">Contact Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Primary Phone</dt>
                    <dd class="col-sm-8">{{ $organization->primary_phone ?? '-' }}</dd>
                    <dt class="col-sm-4">Alternate Phone</dt>
                    <dd class="col-sm-8">{{ $organization->alternate_phone ?? '-' }}</dd>
                    <dt class="col-sm-4">Primary Email</dt>
                    <dd class="col-sm-8">{{ $organization->primary_email ?? '-' }}</dd>
                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">{{ $organization->website ?? '-' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-12">
            <div class="page-surface p-4">
                <h2 class="h6 text-uppercase text-muted mb-3">Statutory Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-2">PAN</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->pan_number ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->pan_number) ?? '-') }}</dd>
                    <dt class="col-sm-2">TAN</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->tan_number ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->tan_number) ?? '-') }}</dd>

                    <dt class="col-sm-2">GSTIN</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->gstin ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->gstin) ?? '-') }}</dd>
                    <dt class="col-sm-2">Professional Tax No.</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->professional_tax_number ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->professional_tax_number) ?? '-') }}</dd>

                    <dt class="col-sm-2">PF Registration No.</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->pf_registration_number ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->pf_registration_number) ?? '-') }}</dd>
                    <dt class="col-sm-2">ESI Registration No.</dt>
                    <dd class="col-sm-4">{{ $canEdit ? ($organization->esi_registration_number ?? '-') : (\App\Models\Organization::maskStatutoryNumber($organization->esi_registration_number) ?? '-') }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-12">
            <div class="page-surface p-4">
                <h2 class="h6 text-uppercase text-muted mb-3">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Created By</dt>
                    <dd class="col-sm-3">{{ $organization->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-3">Last Updated By</dt>
                    <dd class="col-sm-3">{{ $organization->updatedBy?->name ?? '-' }}</dd>

                    <dt class="col-sm-3">Created At</dt>
                    <dd class="col-sm-3">{{ $organization->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-3">Updated At</dt>
                    <dd class="col-sm-3">{{ $organization->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
