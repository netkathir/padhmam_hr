@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Section Master', 'url' => route('masters.sections.index')], ['label' => $section->section_name]]" />

    <x-page-header :title="$section->section_name" :subtitle="$section->section_code">
        @can('update', $section)
            <a href="{{ route('masters.sections.edit', $section) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('activate', $section)
            @if(!$section->isActive())
                <form method="post" action="{{ route('masters.sections.activate', $section) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $section)
            @if($section->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateSectionModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$section->status" />
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Section Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Department</dt>
                    <dd class="col-sm-7">
                        @can('view', $section->department)
                            <a href="{{ route('masters.departments.show', $section->department) }}">{{ $section->department?->department_name }}</a>
                        @else
                            {{ $section->department?->department_name }}
                        @endcan
                    </dd>
                    <dt class="col-sm-5">Short Name</dt>
                    <dd class="col-sm-7">{{ $section->short_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Display Order</dt>
                    <dd class="col-sm-7">{{ $section->display_order }}</dd>
                    <dt class="col-sm-5">Description</dt>
                    <dd class="col-sm-7">{{ $section->description ?? '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $section->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $section->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $section->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $section->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @can('inactivate', $section)
        <form id="inactivate-section-form" method="post" action="{{ route('masters.sections.inactivate', $section) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <x-confirmation-modal
            id="inactivateSectionModal"
            title="Inactivate Section"
            :message="'Are you sure you want to inactivate '.$section->section_name.'? This section will no longer be available for selection.'"
            form="inactivate-section-form"
        >Inactivate</x-confirmation-modal>
    @endcan
@endsection
