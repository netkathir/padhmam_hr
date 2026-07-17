@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branch Engagements']]" />

    <x-page-header title="Branch Engagements" subtitle="Manage contractor engagements for the active branch.">
        @can('create', \App\Models\ContractorBranchEngagement::class)
            <a href="{{ route('contractors.engagements.create') }}" class="btn btn-primary">New Engagement</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Branch Engagements." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('contractors.engagements.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Contractor</label>
                    <select name="contractor_id" class="form-select">
                        <option value="">All</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) ($filters['contractor_id'] ?? '') === (string) $contractor->id)>{{ $contractor->legal_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Agreement Number</label>
                    <input type="text" name="agreement_number" class="form-control" value="{{ $filters['agreement_number'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('contractors.engagements.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Agreement Number</th>
                    <th>Contract Start</th>
                    <th>Contract End</th>
                    <th>Max Labour</th>
                    <th>Contract Validity</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($engagements as $engagement)
                    <tr>
                        <td>{{ $engagement->contractor->contractor_code }} — {{ $engagement->contractor->legal_name }}</td>
                        <td>{{ $engagement->agreement_number ?? '-' }}</td>
                        <td>{{ $engagement->contract_start_date?->format(config('hrms.date_format')) }}</td>
                        <td>{{ $engagement->contract_end_date?->format(config('hrms.date_format')) ?? 'Open' }}</td>
                        <td>{{ $engagement->maximum_labour_count ?? 'Not configured' }}</td>
                        <td><x-validity-badge :label="$engagement->contractValidityStatus()" /></td>
                        <td><x-status-badge :status="$engagement->status" /></td>
                        <td class="text-end">
                            <a href="{{ route('contractors.engagements.show', $engagement) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $engagement)
                                <a href="{{ route('contractors.engagements.edit', $engagement) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-empty-state title="No engagements yet" message="Create the first Branch Engagement to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$engagements" />
        </div>
    @endif
@endsection
