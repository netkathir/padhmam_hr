@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Department Master', 'url' => route('masters.departments.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Department" subtitle="Add a new department to the active branch.">
        <x-cancel-button href="{{ route('masters.departments.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.departments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4"><x-form.input name="department_code" label="Department Code" placeholder="e.g. PROD" required /></div>
                <div class="col-md-8"><x-form.input name="department_name" label="Department Name" required /></div>
                <div class="col-md-6"><x-form.input name="short_name" label="Short Name" /></div>
                <div class="col-md-3"><x-form.input type="number" name="display_order" label="Display Order" value="0" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                </div>
                <div class="col-12"><x-form.textarea name="description" label="Description" /></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Department" />
                <x-cancel-button href="{{ route('masters.departments.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
