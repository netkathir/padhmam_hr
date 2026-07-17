@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Contractor Master']]" />

    <x-page-header title="Contractor Master" subtitle="Manage contractors who supply labour to your branches.">
        @can('create', \App\Models\Contractor::class)
            <a href="{{ route('contractors.master.create') }}" class="btn btn-primary">New Contractor</a>
        @endcan
    </x-page-header>

    <div class="page-surface p-3 mb-3">
        <form method="get" action="{{ route('contractors.master.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Code, name, or phone">
            </div>
            <div class="col-md-2">
                <label class="form-label">Contractor Type</label>
                <select name="contractor_type" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Models\Contractor::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['contractor_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(!$isBranchRestricted)
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <label class="form-label">Licence Validity</label>
                <select name="licence_validity" class="form-select">
                    <option value="">All</option>
                    <option value="valid" @selected(($filters['licence_validity'] ?? '') === 'valid')>Valid</option>
                    <option value="expiring_soon" @selected(($filters['licence_validity'] ?? '') === 'expiring_soon')>Expiring Soon</option>
                    <option value="expired" @selected(($filters['licence_validity'] ?? '') === 'expired')>Expired</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                <a href="{{ route('contractors.master.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
            </div>
        </form>
    </div>

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Contractor Code</th>
                <th>Legal Name</th>
                <th>Trade Name</th>
                <th>Contact</th>
                <th>PAN</th>
                <th>Branches</th>
                <th>Active Engagements</th>
                <th>Licence</th>
                <th>Status</th>
                <th>Updated</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($contractors as $contractor)
                <tr>
                    <td>{{ $contractor->contractor_code }}</td>
                    <td>{{ $contractor->legal_name }}</td>
                    <td>{{ $contractor->trade_name ?? '-' }}</td>
                    <td>{{ $contractor->contact_person_name }}<br><small class="text-muted">{{ $contractor->primary_phone }}</small></td>
                    <td>{{ (auth()->user()?->can('update', $contractor)) ? ($contractor->pan_number ?? '-') : \App\Models\Contractor::maskStatutoryNumber($contractor->pan_number) ?? '-' }}</td>
                    <td>{{ $contractor->branch_count }}</td>
                    <td>{{ $contractor->active_engagement_count }}</td>
                    <td><x-validity-badge :label="$contractor->licenceValidityLabel()" /></td>
                    <td><x-status-badge :status="$contractor->status" /></td>
                    <td>{{ $contractor->updated_at?->format(config('hrms.date_format')) }}</td>
                    <td class="text-end">
                        <a href="{{ route('contractors.master.show', $contractor) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('update', $contractor)
                            <a href="{{ route('contractors.master.edit', $contractor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11"><x-empty-state title="No contractors yet" message="Create the first contractor to get started." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$contractors" />
    </div>
@endsection
