@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Designation Master', 'url' => route('masters.designations.index')], ['label' => $designation->designation_name]]" />

    <x-page-header :title="$designation->designation_name" :subtitle="$designation->designation_code">
        @can('update', $designation)
            <a href="{{ route('masters.designations.edit', $designation) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('activate', $designation)
            @if(!$designation->isActive())
                <form method="post" action="{{ route('masters.designations.activate', $designation) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $designation)
            @if($designation->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateDesignationModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$designation->status" />
            <span class="badge bg-light text-dark border">{{ $designation->scopeLevelLabel() }}</span>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Designation Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Short Name</dt>
                    <dd class="col-sm-7">{{ $designation->short_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Department</dt>
                    <dd class="col-sm-7">
                        @if($designation->department)
                            @can('view', $designation->department)
                                <a href="{{ route('masters.departments.show', $designation->department) }}">{{ $designation->department->department_name }}</a>
                            @else
                                {{ $designation->department->department_name }}
                            @endcan
                        @else
                            -
                        @endif
                    </dd>
                    <dt class="col-sm-5">Section</dt>
                    <dd class="col-sm-7">
                        @if($designation->section)
                            @can('view', $designation->section)
                                <a href="{{ route('masters.sections.show', $designation->section) }}">{{ $designation->section->section_name }}</a>
                            @else
                                {{ $designation->section->section_name }}
                            @endcan
                        @else
                            -
                        @endif
                    </dd>
                    <dt class="col-sm-5">Hierarchy Level</dt>
                    <dd class="col-sm-7">{{ $designation->hierarchy_level ?? '-' }}</dd>
                    <dt class="col-sm-5">Display Order</dt>
                    <dd class="col-sm-7">{{ $designation->display_order }}</dd>
                    <dt class="col-sm-5">Description</dt>
                    <dd class="col-sm-7">{{ $designation->description ?? '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $designation->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $designation->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $designation->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $designation->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @can('inactivate', $designation)
        <form id="inactivate-designation-form" method="post" action="{{ route('masters.designations.inactivate', $designation) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateDesignationModal"
            title="Inactivate Designation"
            :message="'Are you sure you want to inactivate '.$designation->designation_name.'? This designation will no longer be available for selection.'"
            form="inactivate-designation-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
