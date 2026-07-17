@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employees', 'url' => route('employees.index')], ['label' => 'Register Employee']]" />

    <x-page-header title="Register Employee" subtitle="Create a new Employee for the active branch.">
        <x-cancel-button href="{{ route('employees.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('employees.draft.store') }}" enctype="multipart/form-data" id="employee-form">
            @csrf

            @php($employee = null)
            @include('employees.partials.form-fields')

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save as Draft" />
                <x-cancel-button href="{{ route('employees.index') }}">Cancel</x-cancel-button>
            </div>
            <p class="text-muted small mt-2 mb-0">Complete the remaining details and Employee Number generation after saving the Draft.</p>
        </form>
    </div>
@endsection
