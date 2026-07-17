@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Type Master']]" />

    <x-page-header title="Employee Type Master" subtitle="Organization-level classifications used by future Employee, Shift, Attendance, Leave, Contractor, and Payroll modules." />

    <div class="page-surface p-3 mb-3">
        <form method="get" action="{{ route('masters.employee-types.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Code or name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Default Shift Type</label>
                <select name="default_shift_type" class="form-select">
                    <option value="">All</option>
                    <option value="fixed" @selected(($filters['default_shift_type'] ?? '') === 'fixed')>Fixed</option>
                    <option value="rotational" @selected(($filters['default_shift_type'] ?? '') === 'rotational')>Rotational</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Contractor Required</label>
                <select name="requires_contractor" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['requires_contractor'] ?? '') === '1')>Yes</option>
                    <option value="0" @selected(($filters['requires_contractor'] ?? '') === '0')>No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payroll Applicable</label>
                <select name="payroll_applicable" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['payroll_applicable'] ?? '') === '1')>Yes</option>
                    <option value="0" @selected(($filters['payroll_applicable'] ?? '') === '0')>No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                <a href="{{ route('masters.employee-types.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
            </div>
        </form>
    </div>

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Contractor Required</th>
                <th>Default Shift Type</th>
                <th>Attendance</th>
                <th>Leave</th>
                <th>Payroll</th>
                <th>Overtime</th>
                <th>System Record</th>
                <th>Status</th>
                <th>Display Order</th>
                <th>Updated</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($employeeTypes as $employeeType)
                <tr>
                    <td>{{ $employeeType->code }}</td>
                    <td>{{ $employeeType->name }}</td>
                    <td><x-boolean-badge :value="$employeeType->requires_contractor" /></td>
                    <td>{{ ucfirst($employeeType->default_shift_type) }}</td>
                    <td><x-boolean-badge :value="$employeeType->attendance_applicable" /></td>
                    <td><x-boolean-badge :value="$employeeType->leave_applicable" /></td>
                    <td><x-boolean-badge :value="$employeeType->payroll_applicable" /></td>
                    <td><x-boolean-badge :value="$employeeType->overtime_applicable" /></td>
                    <td><x-boolean-badge :value="$employeeType->is_system" true-label="System" false-label="Custom" /></td>
                    <td><x-status-badge :status="$employeeType->status" /></td>
                    <td>{{ $employeeType->display_order }}</td>
                    <td>{{ $employeeType->updated_at?->format(config('hrms.date_format')) }}</td>
                    <td class="text-end">
                        <a href="{{ route('masters.employee-types.show', $employeeType) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('update', $employeeType)
                            <a href="{{ route('masters.employee-types.edit', $employeeType) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13"><x-empty-state title="No Employee Types found" message="Employee Types are seeded automatically as mandatory system classifications." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$employeeTypes" />
    </div>
@endsection
