@extends('layouts.admin')

@php
    $statusBadgeClass = match ($assignment->status) {
        'active' => 'bg-success',
        'scheduled' => 'bg-info text-dark',
        'completed' => 'bg-secondary',
        'cancelled' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'Details']]" />

    <x-page-header title="Shift Assignment Details" subtitle="{{ $assignment->employee?->display_name }} ({{ $assignment->employee?->employee_number }})">
        <div class="d-flex gap-2">
            @can('editScheduled', $assignment)
                @if($assignment->isScheduled())
                    <a href="{{ route('employee-shifts.edit', $assignment) }}" class="btn btn-outline-primary">Edit</a>
                @endif
            @endcan
            @can('cancel', $assignment)
                @if(in_array($assignment->status, ['scheduled', 'active']))
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelAssignmentModal">Cancel</button>
                @endif
            @endcan
            <a href="{{ route('employees.show', $assignment->employee) }}" class="btn btn-outline-secondary">Back to Employee</a>
        </div>
    </x-page-header>

    <div class="page-surface p-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Employee</dt>
            <dd class="col-sm-9">{{ $assignment->employee?->display_name }} ({{ $assignment->employee?->employee_number }})</dd>

            <dt class="col-sm-3">Shift</dt>
            <dd class="col-sm-9">{{ $assignment->shift?->shift_name }}</dd>

            <dt class="col-sm-3">Assignment Type</dt>
            <dd class="col-sm-9">{{ ucfirst($assignment->assignment_type) }}</dd>

            <dt class="col-sm-3">Effective From</dt>
            <dd class="col-sm-9">{{ $assignment->effective_from?->format(config('hrms.date_format')) }}</dd>

            <dt class="col-sm-3">Effective To</dt>
            <dd class="col-sm-9">{{ $assignment->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><span class="badge {{ $statusBadgeClass }}">{{ ucfirst($assignment->status) }}</span></dd>

            <dt class="col-sm-3">Current</dt>
            <dd class="col-sm-9"><x-boolean-badge :value="$assignment->is_current" /></dd>

            @if($assignment->assignment_reason)
                <dt class="col-sm-3">Reason</dt>
                <dd class="col-sm-9">{{ $assignment->assignment_reason }}</dd>
            @endif

            @if($assignment->change_reference)
                <dt class="col-sm-3">Changed From</dt>
                <dd class="col-sm-9"><a href="{{ route('employee-shifts.show', $assignment->change_reference) }}">Assignment #{{ $assignment->change_reference }}</a></dd>
            @endif

            @if($assignment->isCancelled())
                <dt class="col-sm-3">Cancelled At</dt>
                <dd class="col-sm-9">{{ $assignment->cancelled_at?->format(config('hrms.date_format').' h:i A') }}</dd>
                <dt class="col-sm-3">Cancellation Reason</dt>
                <dd class="col-sm-9">{{ $assignment->cancellation_reason }}</dd>
            @endif

            <dt class="col-sm-3">Created By</dt>
            <dd class="col-sm-9">{{ $assignment->createdBy?->name }} on {{ $assignment->created_at?->format(config('hrms.date_format').' h:i A') }}</dd>

            <dt class="col-sm-3">Last Updated By</dt>
            <dd class="col-sm-9">{{ $assignment->updatedBy?->name }} on {{ $assignment->updated_at?->format(config('hrms.date_format').' h:i A') }}</dd>
        </dl>
    </div>

    @can('cancel', $assignment)
        @if(in_array($assignment->status, ['scheduled', 'active']))
            <form id="cancel-assignment-form" method="post" action="{{ route('employee-shifts.cancel', $assignment) }}" class="d-none">
                @csrf
                @method('PATCH')
            </form>
            <div class="modal fade" id="cancelAssignmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cancel Shift Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Cancellation Reason</label>
                            <textarea class="form-control" form="cancel-assignment-form" name="cancellation_reason" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger" form="cancel-assignment-form">Confirm Cancellation</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection
