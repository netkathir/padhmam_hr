@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Rules', 'url' => route('employee-numbering.rules.index')], ['label' => $rule->rule_name]]" />

    <x-page-header :title="$rule->rule_name" :subtitle="$rule->branch->branch_name.' — '.$rule->employeeType->name">
        @can('update', $rule)
            <a href="{{ route('employee-numbering.rules.edit', $rule) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('createVersion', $rule)
            <form method="post" action="{{ route('employee-numbering.rules.new-version', $rule) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Create New Version</button>
            </form>
        @endcan
        @can('activate', $rule)
            @if(!$rule->isActive())
                <form method="post" action="{{ route('employee-numbering.rules.activate', $rule) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $rule)
            @if($rule->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateRuleModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <x-status-badge :status="$rule->status" />
        <x-validity-badge :label="$rule->effectivePeriodStatus()" />
        @if($rule->hasIssuedNumbers())
            <span class="badge bg-info text-dark"><i class="bi bi-lock-fill me-1"></i>Numbers Issued — Format Locked</span>
        @endif
        @if($collisionRisk)
            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>No Branch Code — Possible Collision Risk</span>
        @endif
    </div>

    <div class="page-surface p-4 mb-4">
        <div class="p-3 rounded-3 bg-light border mb-4">
            <div class="small text-muted mb-1">Current Format Preview</div>
            <div class="fs-4 fw-semibold">{{ $preview['preview'] }}</div>
            <div class="small text-muted mt-2">Components: {{ implode(' | ', $preview['components']) }}</div>
            <div class="small text-muted">Sequence period: {{ $preview['sequence_period'] }} &middot; Next serial: {{ $preview['serial'] }}</div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Format Configuration</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Static Prefix</dt>
                    <dd class="col-sm-6">{{ $rule->prefix ?? '-' }}</dd>
                    <dt class="col-sm-6">Include Branch Code</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$rule->include_branch_code" /></dd>
                    <dt class="col-sm-6">Include Employee Type Prefix</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$rule->include_employee_type_prefix" /></dd>
                    <dt class="col-sm-6">Employee Type Prefix</dt>
                    <dd class="col-sm-6">{{ $rule->employee_type_prefix ?? '-' }}</dd>
                    <dt class="col-sm-6">Include Year</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$rule->include_year" /></dd>
                    <dt class="col-sm-6">Year Format</dt>
                    <dd class="col-sm-6">{{ $rule->year_format ?? '-' }}</dd>
                    <dt class="col-sm-6">Separator</dt>
                    <dd class="col-sm-6">{{ $rule->separator !== '' ? $rule->separator : 'No Separator' }}</dd>
                    <dt class="col-sm-6">Serial Number Length</dt>
                    <dd class="col-sm-6">{{ $rule->serial_number_length }}</dd>
                    <dt class="col-sm-6">Starting Number</dt>
                    <dd class="col-sm-6">{{ str_pad($rule->starting_number, $rule->serial_number_length, '0', STR_PAD_LEFT) }}</dd>
                    <dt class="col-sm-6">Reset Frequency</dt>
                    <dd class="col-sm-6">{{ ucfirst(str_replace('_', ' ', $rule->reset_frequency)) }}</dd>
                    <dt class="col-sm-6">Default Rule</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$rule->is_default" /></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Effective From</dt>
                    <dd class="col-sm-6">{{ $rule->effective_from?->format(config('hrms.date_format')) }}</dd>
                    <dt class="col-sm-6">Effective To</dt>
                    <dd class="col-sm-6">{{ $rule->effective_to?->format(config('hrms.date_format')) ?? 'Open ended' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Created By</dt>
                    <dd class="col-sm-6">{{ $rule->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-6">Created At</dt>
                    <dd class="col-sm-6">{{ $rule->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-6">Last Updated By</dt>
                    <dd class="col-sm-6">{{ $rule->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-6">Last Updated At</dt>
                    <dd class="col-sm-6">{{ $rule->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($rule->description)
            <hr>
            <h2 class="h6 text-uppercase text-muted mb-2">Description</h2>
            <p class="mb-0">{{ $rule->description }}</p>
        @endif
    </div>

    <div class="page-surface p-4">
        <h2 class="h6 text-uppercase text-muted mb-3">Sequence History</h2>
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Sequence Period</th>
                <th>Starting Number</th>
                <th>Last Issued Number</th>
                <th>Next Number</th>
                <th>Last Generated</th>
            </tr>
            </thead>
            <tbody>
            @forelse($sequences as $sequence)
                <tr>
                    <td>{{ $sequence->sequence_period }}</td>
                    <td>{{ $rule->starting_number }}</td>
                    <td>{{ $sequence->last_issued_number }}</td>
                    <td>{{ $sequence->next_number }}</td>
                    <td>{{ $sequence->last_generated_at?->format(config('hrms.date_format').' H:i') ?? 'Never' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><x-empty-state title="No sequence history yet" message="A sequence period is created the first time this rule is activated." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
    </div>

    @can('inactivate', $rule)
        <form id="inactivate-rule-form" method="post" action="{{ route('employee-numbering.rules.inactivate', $rule) }}" class="d-none">
            @csrf
            @method('PATCH')
            <input type="hidden" name="reason" id="inactivate-reason-hidden">
        </form>
        <div class="modal fade" id="inactivateRuleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Inactivate Employee Number Rule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if($isOnlyApplicableRule)
                            <div class="alert alert-warning">
                                This is currently the only applicable rule for {{ $rule->employeeType->name }} at {{ $rule->branch->branch_name }}. Inactivating it will block future Employee Registration for this combination until another rule is activated.
                            </div>
                        @endif
                        <p>Are you sure you want to inactivate "{{ $rule->rule_name }}"?</p>
                        <label class="form-label" for="inactivate-reason">Reason</label>
                        <textarea class="form-control" id="inactivate-reason" rows="2"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm-inactivate-rule">Inactivate</button>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
        <script>
            document.getElementById('confirm-inactivate-rule').addEventListener('click', function () {
                document.getElementById('inactivate-reason-hidden').value = document.getElementById('inactivate-reason').value;
                document.getElementById('inactivate-rule-form').submit();
            });
        </script>
        @endpush
    @endcan
@endsection
