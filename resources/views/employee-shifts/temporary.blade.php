@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'Assign Temporary Shift']]" />

    <x-page-header title="Assign Temporary Shift" subtitle="{{ $employee->display_name }} ({{ $employee->employee_number }})">
        <x-cancel-button href="{{ route('employees.show', $employee) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('employee-shifts.temporary.store', $employee) }}">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-form.select name="shift_id" label="Temporary Shift" :options="$shifts->pluck('shift_name', 'id')" required />
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" value="{{ now()->toDateString() }}" required /></div>
                <div class="col-md-3"><x-form.input type="date" name="effective_to" label="Effective To" required /></div>
            </div>

            <div class="row g-3">
                <div class="col-md-8"><x-form.textarea name="reason" label="Reason" required /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Assign Temporary Shift" />
                <x-cancel-button href="{{ route('employees.show', $employee) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
