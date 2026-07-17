@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Sequences']]" />

    <x-page-header title="Employee Number Sequences" subtitle="Sequence administration for the active branch." />

    <x-branch-context-badge />

    @if($requiresBranchSelection)
        <div class="page-surface p-3">
            <x-empty-state title="Select a branch" message="Choose a specific branch from the header selector to view its Employee Number Sequences." />
        </div>
    @else
        <div class="page-surface p-3 mb-3">
            <form method="get" action="{{ route('employee-numbering.sequences.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Rule</label>
                    <select name="rule_id" class="form-select">
                        <option value="">All</option>
                        @foreach($rules as $rule)
                            <option value="{{ $rule->id }}" @selected((string) ($filters['rule_id'] ?? '') === (string) $rule->id)>{{ $rule->rule_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rule Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="{{ route('employee-numbering.sequences.index') }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="page-surface p-3">
            <x-data-table class="table mb-0">
                <thead>
                <tr>
                    <th>Rule</th>
                    <th>Branch</th>
                    <th>Employee Type</th>
                    <th>Sequence Period</th>
                    <th>Starting Number</th>
                    <th>Last Issued</th>
                    <th>Next Number</th>
                    <th>Last Generated</th>
                    <th>Rule Status</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sequences as $sequence)
                    <tr>
                        <td>{{ $sequence->rule->rule_name }}</td>
                        <td>{{ $sequence->rule->branch->branch_name }}</td>
                        <td>{{ $sequence->rule->employeeType->name }}</td>
                        <td>{{ $sequence->sequence_period }}</td>
                        <td>{{ $sequence->rule->starting_number }}</td>
                        <td>{{ $sequence->last_issued_number }}</td>
                        <td>{{ $sequence->next_number }}</td>
                        <td>{{ $sequence->last_generated_at?->format(config('hrms.date_format').' H:i') ?? 'Never' }}</td>
                        <td><x-status-badge :status="$sequence->rule->status" /></td>
                        <td class="text-end">
                            <a href="{{ route('employee-numbering.sequences.show', $sequence) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10"><x-empty-state title="No sequences yet" message="Sequences are created automatically the first time a rule is activated." /></td>
                    </tr>
                @endforelse
                </tbody>
            </x-data-table>
            <x-pagination :paginator="$sequences" />
        </div>
    @endif
@endsection
