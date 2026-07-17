@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Section Master', 'url' => route('masters.sections.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Section" subtitle="Add a new section under a department in the active branch.">
        <x-cancel-button href="{{ route('masters.sections.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.sections.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-form.select name="department_id" label="Department" :options="$departments->pluck('department_name', 'id')" required>
                        <option value="">Select a Department</option>
                    </x-form.select>
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-4"><x-form.input name="section_code" label="Section Code" placeholder="e.g. ASSY" required /></div>
                <div class="col-md-8"><x-form.input name="section_name" label="Section Name" required /></div>
                <div class="col-md-6"><x-form.input name="short_name" label="Short Name" /></div>
                <div class="col-md-3"><x-form.input type="number" name="display_order" label="Display Order" value="0" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                </div>
                <div class="col-12"><x-form.textarea name="description" label="Description" /></div>
            </div>

            @if($departments->isEmpty())
                <div class="alert alert-warning">No active Departments are available in the active branch. Create a Department first.</div>
            @endif

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Section" />
                <x-cancel-button href="{{ route('masters.sections.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
