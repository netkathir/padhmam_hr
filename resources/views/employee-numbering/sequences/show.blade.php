@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Sequences', 'url' => route('employee-numbering.sequences.index')], ['label' => $sequence->sequence_period]]" />

    <x-page-header :title="$sequence->rule->rule_name" :subtitle="'Sequence period: '.$sequence->sequence_period">
        @can('adjust', $sequence)
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#adjustSequenceModal">Set Next Number</button>
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Rule</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Rule Name</dt>
                    <dd class="col-sm-7"><a href="{{ route('employee-numbering.rules.show', $sequence->rule) }}">{{ $sequence->rule->rule_name }}</a></dd>
                    <dt class="col-sm-5">Branch</dt>
                    <dd class="col-sm-7">{{ $sequence->rule->branch->branch_name }}</dd>
                    <dt class="col-sm-5">Employee Type</dt>
                    <dd class="col-sm-7">{{ $sequence->rule->employeeType->name }}</dd>
                    <dt class="col-sm-5">Rule Status</dt>
                    <dd class="col-sm-7"><x-status-badge :status="$sequence->rule->status" /></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Sequence</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Sequence Period</dt>
                    <dd class="col-sm-7">{{ $sequence->sequence_period }}</dd>
                    <dt class="col-sm-5">Starting Number</dt>
                    <dd class="col-sm-7">{{ $sequence->rule->starting_number }}</dd>
                    <dt class="col-sm-5">Last Issued Number</dt>
                    <dd class="col-sm-7">{{ $sequence->last_issued_number }}</dd>
                    <dt class="col-sm-5">Next Number</dt>
                    <dd class="col-sm-7 fw-semibold">{{ $sequence->next_number }}</dd>
                    <dt class="col-sm-5">Last Generated</dt>
                    <dd class="col-sm-7">{{ $sequence->last_generated_at?->format(config('hrms.date_format').' H:i') ?? 'Never' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @can('adjust', $sequence)
        <form id="adjust-sequence-form" method="post" action="{{ route('employee-numbering.sequences.adjust', $sequence) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <div class="modal fade" id="adjustSequenceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Next Number</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            This directly changes the next serial to be issued. Use this only for authorized migration or correction — it cannot go below the last issued number ({{ $sequence->last_issued_number }}) or any currently reserved serial.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Next Number</label>
                            <input type="text" class="form-control" value="{{ $sequence->next_number }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="adjust-next-number">New Next Number <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="adjust-next-number" form="adjust-sequence-form" name="next_number" required>
                            @error('next_number')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="adjust-reason">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="adjust-reason" form="adjust-sequence-form" name="reason" rows="2" required></textarea>
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="adjust-sequence-form" class="btn btn-danger">Confirm Adjustment</button>
                    </div>
                </div>
            </div>
        </div>
        @if($errors->has('next_number') || $errors->has('reason'))
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new bootstrap.Modal(document.getElementById('adjustSequenceModal')).show();
                });
            </script>
            @endpush
        @endif
    @endcan
@endsection
