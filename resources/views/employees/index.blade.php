@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employees']]" />

    <x-page-header title="Employees" subtitle="Manage Employees for the active branch.">
        @can('create', \App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary">Register Employee</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Employees." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Employee number or name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee Type</label>
                    <select name="employee_type_id" class="form-select">
                        <option value="">All</option>
                        @foreach($employeeTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) ($filters['employee_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        <option value="separated" @selected(($filters['status'] ?? '') === 'separated')>Separated</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Employee Number</th>
                    <th>Name</th>
                    <th>Employee Type</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Contractor</th>
                    <th>Shift</th>
                    <th>Date of Joining</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_number ?? 'Draft' }}</td>
                        <td>{{ $employee->display_name }}</td>
                        <td><span class="badge bg-secondary-subtle text-dark border">{{ $employee->employeeType->name }}</span></td>
                        <td>{{ $employee->department?->department_name ?? '-' }}</td>
                        <td>{{ $employee->designation?->designation_name ?? '-' }}</td>
                        <td>{{ $employee->contractor?->legal_name ?? '-' }}</td>
                        <td>
                            {{ ucfirst($employee->shift_type) }}
                            @if($employee->fixedShift)
                                <br><small class="text-muted">{{ $employee->fixedShift->shift_name }}</small>
                            @endif
                        </td>
                        <td>{{ $employee->date_of_joining?->format(config('hrms.date_format')) }}</td>
                        <td><x-status-badge :status="$employee->status" /></td>
                        <td>{{ $employee->updated_at?->format(config('hrms.date_format')) }}</td>
                        <td class="text-end">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $employee)
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11"><x-empty-state title="No employees yet" message="Register the first Employee to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$employees" />
        </div>
    @endif
@endsection
