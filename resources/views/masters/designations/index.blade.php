@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Designation Master']]" />

    <x-page-header title="Designation Master" subtitle="Manage designations within the active branch.">
        @can('create', \App\Models\Designation::class)
            <a href="{{ route('masters.designations.create') }}" class="btn btn-primary">New Designation</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Designation Master." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('masters.designations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Code or name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Scope</label>
                    <select name="scope" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Models\Designation::SCOPE_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['scope'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select name="section_id" class="form-select">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>{{ $section->section_name }}</option>
                        @endforeach
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
                    <a href="{{ route('masters.designations.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Designation Code</th>
                    <th>Designation Name</th>
                    <th>Scope</th>
                    <th>Department</th>
                    <th>Section</th>
                    <th>Hierarchy Level</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($designations as $designation)
                    <tr>
                        <td>{{ $designation->designation_code }}</td>
                        <td>{{ $designation->designation_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $designation->scopeLevelLabel() }}</span></td>
                        <td>{{ $designation->department?->department_name ?? '-' }}</td>
                        <td>{{ $designation->section?->section_name ?? '-' }}</td>
                        <td>{{ $designation->hierarchy_level ?? '-' }}</td>
                        <td><x-status-badge :status="$designation->status" /></td>
                        <td>{{ $designation->updated_at?->format(config('hrms.date_format')) }}</td>
                        <td class="text-end">
                            <a href="{{ route('masters.designations.show', $designation) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $designation)
                                <a href="{{ route('masters.designations.edit', $designation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9"><x-empty-state title="No designations yet" message="Create the first designation to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$designations" />
        </div>
    @endif
@endsection
