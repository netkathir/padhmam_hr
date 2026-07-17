@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employees', 'url' => route('employees.index')], ['label' => $employee->display_name]]" />

    <x-page-header title="Edit Employee" :subtitle="($employee->employee_number ?? 'Draft').' — '.$employee->display_name">
        <x-cancel-button href="{{ $employee->isDraft() ? route('employees.index') : route('employees.show', $employee) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    @if($employee->isDraft())
        <div class="alert alert-info">
            This Employee is still a Draft and has not been assigned an Employee Number. Complete all required sections, then proceed to Review to finish registration.
        </div>
    @endif

    <div class="page-surface p-4">
        <form method="post" action="{{ $employee->isDraft() ? route('employees.draft.update', $employee) : route('employees.update', $employee) }}" enctype="multipart/form-data" id="employee-form">
            @csrf
            @method('PUT')

            @include('employees.partials.form-fields')

            <div class="d-flex gap-2 mt-4">
                <x-submit-button :label="$employee->isDraft() ? 'Save Draft' : 'Save Changes'" />
                @if($employee->isDraft())
                    @can('completeRegistration', $employee)
                        <a href="{{ route('employees.review', $employee) }}" class="btn btn-outline-primary">Proceed to Review</a>
                    @endcan
                @endif
                <x-cancel-button :href="$employee->isDraft() ? route('employees.index') : route('employees.show', $employee)">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
