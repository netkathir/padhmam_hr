@extends('layouts.admin')

@php
    $statusBadgeClass = fn (string $status) => match ($status) {
        'active' => 'bg-success',
        'scheduled' => 'bg-info text-dark',
        'completed' => 'bg-secondary',
        'cancelled' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'History']]" />

    <x-page-header title="Shift Assignment History" subtitle="{{ $employee->display_name }} ({{ $employee->employee_number }})">
        <x-cancel-button href="{{ route('employees.show', $employee) }}">Back to Employee</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Shift</th>
                <th>Type</th>
                <th>Effective From</th>
                <th>Effective To</th>
                <th>Status</th>
                <th>Current</th>
                <th>Created By</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->shift?->shift_name }}</td>
                    <td>{{ ucfirst($assignment->assignment_type) }}</td>
                    <td>{{ $assignment->effective_from?->format(config('hrms.date_format')) }}</td>
                    <td>{{ $assignment->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}</td>
                    <td><span class="badge {{ $statusBadgeClass($assignment->status) }}">{{ ucfirst($assignment->status) }}</span></td>
                    <td><x-boolean-badge :value="$assignment->is_current" /></td>
                    <td>{{ $assignment->createdBy?->name }}</td>
                    <td class="text-end">
                        <a href="{{ route('employee-shifts.show', $assignment) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8"><x-empty-state title="No Shift assignment history" message="This Employee has no Shift assignments yet." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
    </div>
@endsection
