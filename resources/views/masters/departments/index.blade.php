@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Department Master']]" />

    <x-page-header title="Department Master" subtitle="Manage departments for the active branch.">
        @can('create', \App\Models\Department::class)
            <a href="{{ route('masters.departments.create') }}" class="btn btn-primary">New Department</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Department Master." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('masters.departments.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Code or name">
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
                    <a href="{{ route('masters.departments.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Department Code</th>
                    <th>Department Name</th>
                    <th>Short Name</th>
                    <th>Sections</th>
                    <th>Status</th>
                    <th>Display Order</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($departments as $department)
                    <tr>
                        <td>{{ $department->department_code }}</td>
                        <td>{{ $department->department_name }}</td>
                        <td>{{ $department->short_name ?? '-' }}</td>
                        <td>{{ $department->sections_count }}</td>
                        <td><x-status-badge :status="$department->status" /></td>
                        <td>{{ $department->display_order }}</td>
                        <td>{{ $department->updated_at?->format(config('hrms.date_format')) }}</td>
                        <td class="text-end">
                            <a href="{{ route('masters.departments.show', $department) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $department)
                                <a href="{{ route('masters.departments.edit', $department) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-empty-state title="No departments yet" message="Create the first department to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$departments" />
        </div>
    @endif
@endsection
