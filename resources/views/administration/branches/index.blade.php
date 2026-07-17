@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branches']]" />

    <x-page-header title="Branches" subtitle="Manage the company branch master.">
        @can('create', \App\Models\Branch::class)
            <a href="{{ route('branches.create') }}" class="btn btn-primary">New Branch</a>
        @endcan
    </x-page-header>

    <div class="page-surface p-3 mb-3">
        <form method="get" action="{{ route('branches.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Code or name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Branch Type</label>
                <select name="branch_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($branchTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['branch_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="{{ $filters['city'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" value="{{ $filters['state'] ?? '' }}">
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
                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
            </div>
        </form>
    </div>

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>City</th>
                <th>State</th>
                <th>Contact</th>
                <th>Head Office</th>
                <th>Status</th>
                <th>Updated</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($branches as $branch)
                <tr>
                    <td>{{ $branch->branch_code }}</td>
                    <td>{{ $branch->branch_name }}</td>
                    <td>{{ $branch->typeLabel() }}</td>
                    <td>{{ $branch->city ?? '-' }}</td>
                    <td>{{ $branch->state ?? '-' }}</td>
                    <td>{{ $branch->phone ?? '-' }}</td>
                    <td>@if($branch->isHeadOffice())<span class="badge bg-primary">Head Office</span>@endif</td>
                    <td><x-status-badge :status="$branch->status" /></td>
                    <td>{{ $branch->updated_at?->format(config('hrms.date_format')) }}</td>
                    <td class="text-end">
                        <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('update', $branch)
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10"><x-empty-state title="No branches yet" message="Create the first branch to get started." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$branches" />
    </div>
@endsection
