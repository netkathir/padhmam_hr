@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'Edit Scheduled Assignment']]" />

    <x-page-header title="Edit Scheduled Shift Assignment" subtitle="{{ $assignment->employee?->display_name }} ({{ $assignment->employee?->employee_number }})">
        <x-cancel-button href="{{ route('employee-shifts.show', $assignment) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('employee-shifts.update', $assignment) }}">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-form.select name="shift_id" label="Shift" :options="$shifts->pluck('shift_name', 'id')" :value="$assignment->shift_id" required />
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" :value="$assignment->effective_from?->toDateString()" required /></div>
                <div class="col-md-3">
                    <x-form.input type="date" name="effective_to" label="Effective To" :value="$assignment->effective_to?->toDateString()" :required="$assignment->isTemporary()" />
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8"><x-form.textarea name="assignment_reason" label="Assignment Reason" :value="$assignment->assignment_reason" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('employee-shifts.show', $assignment) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
