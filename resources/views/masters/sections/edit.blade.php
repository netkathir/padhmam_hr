@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Section Master', 'url' => route('masters.sections.index')], ['label' => $section->section_name]]" />

    <x-page-header title="Edit Section" :subtitle="$section->section_code.' — '.$section->section_name">
        <x-cancel-button href="{{ route('masters.sections.show', $section) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.sections.update', $section) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <x-form.select name="department_id" label="Department" :options="$departments->pluck('department_name', 'id')" :value="$section->department_id" required />
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-4"><x-form.input name="section_code" label="Section Code" :value="$section->section_code" required /></div>
                <div class="col-md-8"><x-form.input name="section_name" label="Section Name" :value="$section->section_name" required /></div>
                <div class="col-md-6"><x-form.input name="short_name" label="Short Name" :value="$section->short_name" /></div>
                <div class="col-md-3"><x-form.input type="number" name="display_order" label="Display Order" :value="$section->display_order" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$section->status" required />
                </div>
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$section->description" /></div>
            </div>

            @if($section->department && !$section->department->isActive())
                <div class="alert alert-warning">The current Department for this Section is inactive. It is shown for reference, but the Section cannot be made Active while assigned to an inactive Department.</div>
            @endif

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('masters.sections.show', $section) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
