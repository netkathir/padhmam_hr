@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Shift Assignments', 'url' => route('employee-shifts.index')], ['label' => 'Pending']]" />

    <x-page-header title="Shift Assignment Pending" subtitle="Active Employees who have completed registration but have no current Shift assignment.">
        <a href="{{ route('employee-shifts.index') }}" class="btn btn-outline-secondary">Back to All Assignments</a>
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view pending Shift assignments." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('employee-shifts.pending') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Employee name or number">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('employee-shifts.pending') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Employee Number</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Employee Type</th>
                    <th>Shift Type</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_number }}</td>
                        <td>{{ $employee->display_name }}</td>
                        <td>{{ $employee->department?->name }}</td>
                        <td>{{ $employee->designation?->name }}</td>
                        <td>{{ $employee->employeeType?->name }}</td>
                        <td>{{ ucfirst($employee->shift_type) }}</td>
                        <td class="text-end">
                            @can('create', \App\Models\EmployeeShiftAssignment::class)
                                <a href="{{ route('employee-shifts.create', $employee) }}" class="btn btn-sm btn-primary">Assign Shift</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-empty-state title="No pending Employees" message="Every Active Employee currently has a Shift assignment." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$employees" />
        </div>
    @endif
@endsection
