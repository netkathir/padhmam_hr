@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Shift Master']]" />

    <x-page-header title="Shift Master" subtitle="Configure Fixed and Rotational shifts for the active branch.">
        @can('create', \App\Models\Shift::class)
            <a href="{{ route('shifts.master.create') }}" class="btn btn-primary">New Shift</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Shift Master." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('shifts.master.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Shift code or name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Shift Type</label>
                    <select name="shift_type" class="form-select">
                        <option value="">All</option>
                        <option value="fixed" @selected(($filters['shift_type'] ?? '') === 'fixed')>Fixed</option>
                        <option value="rotational" @selected(($filters['shift_type'] ?? '') === 'rotational')>Rotational</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee Type</label>
                    <select name="employee_type_id" class="form-select">
                        <option value="">All</option>
                        @foreach($employeeTypes as $employeeType)
                            <option value="{{ $employeeType->id }}" @selected((string) ($filters['employee_type_id'] ?? '') === (string) $employeeType->id)>{{ $employeeType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Day / Overnight</label>
                    <select name="overnight" class="form-select">
                        <option value="">All</option>
                        <option value="day" @selected(($filters['overnight'] ?? '') === 'day')>Day Shift</option>
                        <option value="overnight" @selected(($filters['overnight'] ?? '') === 'overnight')>Overnight</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective Validity</label>
                    <select name="effective_status" class="form-select">
                        <option value="">All</option>
                        <option value="current" @selected(($filters['effective_status'] ?? '') === 'current')>Current</option>
                        <option value="upcoming" @selected(($filters['effective_status'] ?? '') === 'upcoming')>Upcoming</option>
                        <option value="expired" @selected(($filters['effective_status'] ?? '') === 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('shifts.master.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Shift Code</th>
                    <th>Shift Name</th>
                    <th>Type</th>
                    <th>Timing</th>
                    <th>Duration</th>
                    <th>Employee Types</th>
                    <th>Effective Period</th>
                    <th>Validity</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>{{ $shift->shift_code }}</td>
                        <td>{{ $shift->shift_name }}</td>
                        <td>
                            <span class="badge {{ $shift->isFixed() ? 'bg-primary' : 'bg-info text-dark' }}">{{ ucfirst($shift->shift_type) }}</span>
                        </td>
                        <td>
                            {{ $shift->start_time->format('h:i A') }} &ndash; {{ $shift->end_time->format('h:i A') }}
                            @if($shift->isOvernight())
                                <span class="badge bg-dark"><i class="bi bi-moon-stars me-1"></i>Overnight</span>
                            @else
                                <span class="badge bg-light text-dark border"><i class="bi bi-sun me-1"></i>Day</span>
                            @endif
                        </td>
                        <td>{{ $shift->formattedWorkDuration() }}</td>
                        <td>
                            @forelse($shift->employeeTypes as $employeeType)
                                <span class="badge bg-secondary-subtle text-dark border">{{ $employeeType->name }}</span>
                            @empty
                                <span class="text-muted">None</span>
                            @endforelse
                        </td>
                        <td>
                            {{ $shift->effective_from?->format(config('hrms.date_format')) }}
                            &ndash;
                            {{ $shift->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}
                        </td>
                        <td><x-validity-badge :label="$shift->validityState()" /></td>
                        <td><x-status-badge :status="$shift->status" /></td>
                        <td>{{ $shift->display_order }}</td>
                        <td>{{ $shift->updated_at?->format(config('hrms.date_format')) }}</td>
                        <td class="text-end">
                            <a href="{{ route('shifts.master.show', $shift) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $shift)
                                <a href="{{ route('shifts.master.edit', $shift) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12"><x-empty-state title="No shifts yet" message="Create the first Shift to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$shifts" />
        </div>
    @endif
@endsection
