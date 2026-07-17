@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Shift Master', 'url' => route('shifts.master.index')], ['label' => $shift->shift_name]]" />

    <x-page-header :title="$shift->shift_name" :subtitle="$shift->shift_code">
        @can('update', $shift)
            <a href="{{ route('shifts.master.edit', $shift) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @can('clone', $shift)
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cloneShiftModal">Clone Shift</button>
        @endcan
        @can('activate', $shift)
            @if(!$shift->isActive())
                <form method="post" action="{{ route('shifts.master.activate', $shift) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $shift)
            @if($shift->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateShiftModal">Inactivate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <x-status-badge :status="$shift->status" />
        <x-validity-badge :label="$shift->validityState()" />
        <span class="badge {{ $shift->isFixed() ? 'bg-primary' : 'bg-info text-dark' }}">{{ ucfirst($shift->shift_type) }}</span>
        @if($shift->isOvernight())
            <span class="badge bg-dark"><i class="bi bi-moon-stars me-1"></i>Overnight</span>
        @else
            <span class="badge bg-light text-dark border"><i class="bi bi-sun me-1"></i>Day Shift</span>
        @endif
    </div>

    <div class="page-surface p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Timing</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Start Time</dt>
                    <dd class="col-sm-6">{{ $shift->start_time->format('h:i A') }}</dd>
                    <dt class="col-sm-6">End Time</dt>
                    <dd class="col-sm-6">{{ $shift->end_time->format('h:i A') }}</dd>
                    <dt class="col-sm-6">Gross Duration</dt>
                    <dd class="col-sm-6">{{ $shift->formattedGrossDuration() }}</dd>
                    <dt class="col-sm-6">Break Duration</dt>
                    <dd class="col-sm-6">{{ $shift->formattedBreakDuration() }}</dd>
                    <dt class="col-sm-6">Scheduled Work Duration</dt>
                    <dd class="col-sm-6 fw-semibold">{{ $shift->formattedWorkDuration() }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Grace Periods</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Early Entry Allowed</dt>
                    <dd class="col-sm-6">{{ $shift->early_entry_allowed_minutes }} min</dd>
                    <dt class="col-sm-6">Late Entry Grace</dt>
                    <dd class="col-sm-6">{{ $shift->late_entry_grace_minutes }} min</dd>
                    <dt class="col-sm-6">Early Exit Grace</dt>
                    <dd class="col-sm-6">{{ $shift->early_exit_grace_minutes }} min</dd>
                    <dt class="col-sm-6">Late Exit Allowed</dt>
                    <dd class="col-sm-6">{{ $shift->late_exit_allowed_minutes }} min</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Attendance Thresholds</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Minimum Half-Day</dt>
                    <dd class="col-sm-6">{{ $shift->minimum_half_day_minutes ? $shift->minimum_half_day_minutes.' min' : 'Not configured' }}</dd>
                    <dt class="col-sm-6">Minimum Full-Day</dt>
                    <dd class="col-sm-6">{{ $shift->minimum_full_day_minutes ? $shift->minimum_full_day_minutes.' min' : 'Not configured' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Overtime</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Overtime Applicable</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$shift->overtime_applicable" /></dd>
                    <dt class="col-sm-6">Overtime Start After</dt>
                    <dd class="col-sm-6">{{ $shift->overtime_start_after_minutes ? $shift->overtime_start_after_minutes.' min' : '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Applicable Days</h2>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach(config('hrms.shift_day_codes') as $code => $label)
                        <span class="badge {{ $shift->isApplicableOnDay($code) ? 'bg-success' : 'bg-light text-muted border' }}">{{ $label }}</span>
                    @endforeach
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Compatible Employee Types</h2>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @forelse($shift->employeeTypes as $employeeType)
                        <span class="badge bg-secondary-subtle text-dark border">{{ $employeeType->name }}</span>
                    @empty
                        <span class="text-muted">None configured</span>
                    @endforelse
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Effective From</dt>
                    <dd class="col-sm-7">{{ $shift->effective_from?->format(config('hrms.date_format')) }}</dd>
                    <dt class="col-sm-5">Effective To</dt>
                    <dd class="col-sm-7">{{ $shift->effective_to?->format(config('hrms.date_format')) ?? 'Open ended' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Branch</dt>
                    <dd class="col-sm-7">{{ $shift->branch->branch_name }}</dd>
                    <dt class="col-sm-5">Created By</dt>
                    <dd class="col-sm-7">{{ $shift->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Created At</dt>
                    <dd class="col-sm-7">{{ $shift->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-5">Last Updated By</dt>
                    <dd class="col-sm-7">{{ $shift->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Last Updated At</dt>
                    <dd class="col-sm-7">{{ $shift->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($shift->description)
            <hr>
            <h2 class="h6 text-uppercase text-muted mb-2">Description</h2>
            <p class="mb-0">{{ $shift->description }}</p>
        @endif
    </div>

    @can('clone', $shift)
        <form id="clone-shift-form" method="post" action="{{ route('shifts.master.clone', $shift) }}" class="d-none">
            @csrf
        </form>
        <div class="modal fade" id="cloneShiftModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Clone Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Creates a new Draft Shift in the active Branch with this Shift's configuration. Provide a new code and name.</p>
                        <div class="mb-3">
                            <label class="form-label" for="clone-shift-code">New Shift Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clone-shift-code" form="clone-shift-form" name="shift_code" required>
                            @error('shift_code')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="clone-shift-name">New Shift Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clone-shift-name" form="clone-shift-form" name="shift_name" required>
                            @error('shift_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="clone-shift-form" class="btn btn-primary">Clone</button>
                    </div>
                </div>
            </div>
        </div>
        @if($errors->has('shift_code') || $errors->has('shift_name'))
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new bootstrap.Modal(document.getElementById('cloneShiftModal')).show();
                });
            </script>
            @endpush
        @endif
    @endcan

    @can('inactivate', $shift)
        <form id="inactivate-shift-form" method="post" action="{{ route('shifts.master.inactivate', $shift) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <div class="modal fade" id="inactivateShiftModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Inactivate Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to inactivate "{{ $shift->shift_name }}"? It will no longer be available for future assignment.</p>
                        <label class="form-label" for="inactivate-reason">Reason (optional)</label>
                        <textarea class="form-control" id="inactivate-reason" form="inactivate-shift-form" name="reason" rows="2"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="inactivate-shift-form" class="btn btn-danger">Inactivate</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
