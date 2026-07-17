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
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments']]" />

    <x-page-header title="Employee Shift Assignments" subtitle="View and manage Fixed, Rotational, and Temporary Shift assignments.">
        <a href="{{ route('employee-shifts.pending') }}" class="btn btn-outline-primary">Pending Assignments</a>
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view Shift assignments." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('employee-shifts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Employee name or number">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Assignment Type</label>
                    <select name="assignment_type" class="form-select">
                        <option value="">All</option>
                        <option value="fixed" @selected(($filters['assignment_type'] ?? '') === 'fixed')>Fixed</option>
                        <option value="rotational" @selected(($filters['assignment_type'] ?? '') === 'rotational')>Rotational</option>
                        <option value="temporary" @selected(($filters['assignment_type'] ?? '') === 'temporary')>Temporary</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="scheduled" @selected(($filters['status'] ?? '') === 'scheduled')>Scheduled</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('employee-shifts.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Shift</th>
                    <th>Type</th>
                    <th>Effective From</th>
                    <th>Effective To</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->employee?->display_name }} <span class="text-muted">({{ $assignment->employee?->employee_number }})</span></td>
                        <td>{{ $assignment->shift?->shift_name }}</td>
                        <td>{{ ucfirst($assignment->assignment_type) }}</td>
                        <td>{{ $assignment->effective_from?->format(config('hrms.date_format')) }}</td>
                        <td>{{ $assignment->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}</td>
                        <td><span class="badge {{ $statusBadgeClass($assignment->status) }}">{{ ucfirst($assignment->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('employee-shifts.show', $assignment) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-empty-state title="No Shift assignments yet" message="Assignments will appear here once created." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$assignments" />
        </div>
    @endif
@endsection
