@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Department Master', 'url' => route('masters.departments.index')], ['label' => $department->department_name]]" />

    <x-page-header :title="$department->department_name" :subtitle="$department->department_code">
        @can('update', $department)
            <a href="{{ route('masters.departments.edit', $department) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('activate', $department)
            @if(!$department->isActive())
                <form method="post" action="{{ route('masters.departments.activate', $department) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $department)
            @if($department->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateDepartmentModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$department->status" />
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Department Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Short Name</dt>
                    <dd class="col-sm-7">{{ $department->short_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Display Order</dt>
                    <dd class="col-sm-7">{{ $department->display_order }}</dd>
                    <dt class="col-sm-5">Number of Sections</dt>
                    <dd class="col-sm-7">{{ $sections->count() }}</dd>
                    <dt class="col-sm-5">Description</dt>
                    <dd class="col-sm-7">{{ $department->description ?? '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $department->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $department->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $department->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $department->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="page-surface p-4">
        <h2 class="h6 text-uppercase text-muted mb-3">Sections in this Department</h2>
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Section Code</th>
                <th>Section Name</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($sections as $section)
                <tr>
                    <td>{{ $section->section_code }}</td>
                    <td>{{ $section->section_name }}</td>
                    <td><x-status-badge :status="$section->status" /></td>
                    <td class="text-end">
                        @can('view', $section)
                            <a href="{{ route('masters.sections.show', $section) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4"><x-empty-state title="No sections yet" message="This department has no sections." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
    </div>

    @can('inactivate', $department)
        <form id="inactivate-department-form" method="post" action="{{ route('masters.departments.inactivate', $department) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateDepartmentModal"
            title="Inactivate Department"
            :message="'Are you sure you want to inactivate '.$department->department_name.'? This department will no longer be available for selection.'"
            form="inactivate-department-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
