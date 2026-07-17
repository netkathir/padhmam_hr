@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Department Master', 'url' => route('masters.departments.index')], ['label' => $department->department_name]]" />

    <x-page-header title="Edit Department" :subtitle="$department->department_code.' — '.$department->department_name">
        <x-cancel-button href="{{ route('masters.departments.show', $department) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.departments.update', $department) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4"><x-form.input name="department_code" label="Department Code" :value="$department->department_code" required /></div>
                <div class="col-md-8"><x-form.input name="department_name" label="Department Name" :value="$department->department_name" required /></div>
                <div class="col-md-6"><x-form.input name="short_name" label="Short Name" :value="$department->short_name" /></div>
                <div class="col-md-3"><x-form.input type="number" name="display_order" label="Display Order" :value="$department->display_order" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$department->status" required />
                </div>
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$department->description" /></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('masters.departments.show', $department) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
