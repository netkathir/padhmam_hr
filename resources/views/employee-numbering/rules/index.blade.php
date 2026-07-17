@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Rules']]" />

    <x-page-header title="Employee Number Rules" subtitle="Configure Employee Number formats for the active branch.">
        @can('create', \App\Models\EmployeeNumberRule::class)
            <a href="{{ route('employee-numbering.rules.create') }}" class="btn btn-primary">New Rule</a>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Employee Number Rules." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('employee-numbering.rules.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Rule name or prefix">
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reset Frequency</label>
                    <select name="reset_frequency" class="form-select">
                        <option value="">All</option>
                        <option value="never" @selected(($filters['reset_frequency'] ?? '') === 'never')>Never</option>
                        <option value="yearly" @selected(($filters['reset_frequency'] ?? '') === 'yearly')>Yearly</option>
                        <option value="financial_yearly" @selected(($filters['reset_frequency'] ?? '') === 'financial_yearly')>Financial Yearly</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective Status</label>
                    <select name="effective_status" class="form-select">
                        <option value="">All</option>
                        <option value="current" @selected(($filters['effective_status'] ?? '') === 'current')>Current</option>
                        <option value="upcoming" @selected(($filters['effective_status'] ?? '') === 'upcoming')>Upcoming</option>
                        <option value="expired" @selected(($filters['effective_status'] ?? '') === 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('employee-numbering.rules.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Rule Name</th>
                    <th>Branch</th>
                    <th>Employee Type</th>
                    <th>Format Preview</th>
                    <th>Reset Frequency</th>
                    <th>Serial Length</th>
                    <th>Effective From</th>
                    <th>Effective To</th>
                    <th>Status</th>
                    <th>Sequence Period</th>
                    <th>Next Number</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->rule_name }}</td>
                        <td>{{ $rule->branch->branch_name }}</td>
                        <td>{{ $rule->employeeType->name }}</td>
                        <td><code>{{ $previews[$rule->id]['preview'] }}</code></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $rule->reset_frequency)) }}</td>
                        <td>{{ $rule->serial_number_length }}</td>
                        <td>{{ $rule->effective_from?->format(config('hrms.date_format')) }}</td>
                        <td>{{ $rule->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}</td>
                        <td><x-validity-badge :label="$rule->effectivePeriodStatus()" /></td>
                        <td>{{ $previews[$rule->id]['sequence_period'] }}</td>
                        <td>{{ $previews[$rule->id]['serial'] }}</td>
                        <td>{{ $rule->updated_at?->format(config('hrms.date_format')) }}</td>
                        <td class="text-end">
                            <a href="{{ route('employee-numbering.rules.show', $rule) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $rule)
                                <a href="{{ route('employee-numbering.rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13"><x-empty-state title="No rules yet" message="Create the first Employee Number Rule to get started." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$rules" />
        </div>
    @endif
@endsection
