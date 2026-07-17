@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'Change Fixed Shift']]" />

    <x-page-header title="Change Fixed Shift" subtitle="{{ $employee->display_name }} ({{ $employee->employee_number }})">
        <x-cancel-button href="{{ route('employees.show', $employee) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        @if($employee->currentShiftAssignment)
            <div class="p-3 rounded-3 bg-light border mb-4">
                <div class="small text-muted mb-1">Current Fixed Shift</div>
                <div class="fw-semibold">{{ $employee->currentShiftAssignment->shift?->shift_name }}</div>
                <div class="small text-muted">Effective from {{ $employee->currentShiftAssignment->effective_from?->format(config('hrms.date_format')) }}</div>
            </div>
        @endif

        <form method="post" action="{{ route('employee-shifts.change.store', $employee) }}">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-form.select name="shift_id" label="New Fixed Shift" :options="$shifts->pluck('shift_name', 'id')" required />
                </div>
                <div class="col-md-3">
                    <x-form.input type="date" name="effective_from" label="Effective From" value="{{ now()->addDay()->toDateString() }}" required />
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8"><x-form.textarea name="reason" label="Reason for Change" required /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Change Shift" />
                <x-cancel-button href="{{ route('employees.show', $employee) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
