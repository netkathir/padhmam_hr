@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Shift Master', 'url' => route('shifts.master.index')], ['label' => $shift->shift_name]]" />

    <x-page-header title="Edit Shift" :subtitle="$shift->shift_code.' — '.$shift->shift_name">
        <x-cancel-button href="{{ route('shifts.master.show', $shift) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    @if($shift->hasOperationalUsage())
        <div class="alert alert-warning">
            This Shift already has operational usage. Create a new Shift (via Clone) to change its timing configuration.
        </div>
    @endif

    <div class="page-surface p-4">
        <form method="post" action="{{ route('shifts.master.update', $shift) }}" id="shift-form">
            @csrf
            @method('PUT')

            <h2 class="h6 text-uppercase text-muted mb-3">Basic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input name="shift_code" label="Shift Code" :value="$shift->shift_code" required /></div>
                <div class="col-md-5"><x-form.input name="shift_name" label="Shift Name" :value="$shift->shift_name" required /></div>
                <div class="col-md-4"><x-form.input name="short_name" label="Short Name" :value="$shift->short_name" /></div>
                <div class="col-md-4">
                    @if($shift->hasOperationalUsage())
                        <label class="form-label">Shift Type</label>
                        <input type="text" class="form-control" value="{{ ucfirst($shift->shift_type) }}" disabled>
                    @else
                        <x-form.select name="shift_type" label="Shift Type" :options="['fixed' => 'Fixed', 'rotational' => 'Rotational']" :value="$shift->shift_type" required />
                    @endif
                </div>
                <div class="col-md-4"><x-form.input name="color_code" label="Colour" type="color" :value="$shift->color_code ?? '#1F6FEB'" /></div>
                <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" :value="$shift->display_order" min="0" /></div>
            </div>

            @if($shift->hasOperationalUsage())
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Start Time</label>
                        <input type="text" class="form-control" value="{{ $shift->start_time->format('h:i A') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Time</label>
                        <input type="text" class="form-control" value="{{ $shift->end_time->format('h:i A') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Break Duration</label>
                        <input type="text" class="form-control" value="{{ $shift->formattedBreakDuration() }}" disabled>
                    </div>
                </div>
            @else
                <h2 class="h6 text-uppercase text-muted mb-3">Shift Timing</h2>
                <div class="row g-3 mb-2 shift-timing-input">
                    <div class="col-md-3"><x-form.input type="time" name="start_time" label="Start Time" :value="$shift->start_time->format('H:i')" required /></div>
                    <div class="col-md-3"><x-form.input type="time" name="end_time" label="End Time" :value="$shift->end_time->format('H:i')" required /></div>
                    <div class="col-md-3"><x-form.input type="number" name="break_duration_minutes" label="Break Duration (minutes)" :value="$shift->break_duration_minutes" min="0" required /></div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="small text-muted mb-1">Duration Preview <span class="badge bg-secondary">Server recalculates on save</span></div>
                            <div id="timing-preview-type" class="fw-semibold mb-1">—</div>
                            <div class="small text-muted">Gross Duration: <span id="timing-preview-gross">—</span> &middot; Break: <span id="timing-preview-break">—</span> &middot; Scheduled Work: <span id="timing-preview-work">—</span></div>
                        </div>
                    </div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Grace Periods</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><x-form.input type="number" name="early_entry_allowed_minutes" label="Early Entry Allowed (minutes)" :value="$shift->early_entry_allowed_minutes" min="0" required /></div>
                    <div class="col-md-3"><x-form.input type="number" name="late_entry_grace_minutes" label="Late Entry Grace (minutes)" :value="$shift->late_entry_grace_minutes" min="0" required /></div>
                    <div class="col-md-3"><x-form.input type="number" name="early_exit_grace_minutes" label="Early Exit Grace (minutes)" :value="$shift->early_exit_grace_minutes" min="0" required /></div>
                    <div class="col-md-3"><x-form.input type="number" name="late_exit_allowed_minutes" label="Late Exit Allowed (minutes)" :value="$shift->late_exit_allowed_minutes" min="0" required /></div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Attendance Thresholds</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><x-form.input type="number" name="minimum_half_day_minutes" label="Minimum Half-Day Minutes" :value="$shift->minimum_half_day_minutes" min="1" /></div>
                    <div class="col-md-4"><x-form.input type="number" name="minimum_full_day_minutes" label="Minimum Full-Day Minutes" :value="$shift->minimum_full_day_minutes" min="1" /></div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Overtime Configuration</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4 d-flex align-items-end">
                        <x-form.checkbox name="overtime_applicable" label="Overtime Applicable" :checked="$shift->overtime_applicable" />
                    </div>
                    <div class="col-md-4"><x-form.input type="number" name="overtime_start_after_minutes" label="Overtime Start After Minutes" :value="$shift->overtime_start_after_minutes" min="0" /></div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Applicable Days</h2>
                <div class="row g-3 mb-4">
                    <div class="col-12 d-flex flex-wrap gap-3">
                        @foreach(config('hrms.shift_day_codes') as $code => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="applicable_days[]" value="{{ $code }}" id="day_{{ $code }}" @checked(in_array($code, $shift->applicable_days ?? []))>
                                <label class="form-check-label" for="day_{{ $code }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('applicable_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <h2 class="h6 text-uppercase text-muted mb-3">Employee Type Compatibility</h2>
            <div class="row g-3 mb-4">
                <div class="col-12 d-flex flex-wrap gap-3">
                    @foreach($employeeTypes as $employeeType)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="employee_type_ids[]" value="{{ $employeeType->id }}" id="employee_type_{{ $employeeType->id }}" @checked($shift->employeeTypes->contains('id', $employeeType->id))>
                            <label class="form-check-label" for="employee_type_{{ $employeeType->id }}">{{ $employeeType->name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('employee_type_ids')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            @if(!$shift->hasOperationalUsage())
                <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" :value="$shift->effective_from?->format('Y-m-d')" required /></div>
                    <div class="col-md-3"><x-form.input type="date" name="effective_to" label="Effective To" :value="$shift->effective_to?->format('Y-m-d')" /></div>
                </div>
            @else
                <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Effective From</label>
                        <input type="text" class="form-control" value="{{ $shift->effective_from?->format(config('hrms.date_format')) }}" disabled>
                    </div>
                    <div class="col-md-3"><x-form.input type="date" name="effective_to" label="Effective To" :value="$shift->effective_to?->format('Y-m-d')" /></div>
                </div>
            @endif

            <h2 class="h6 text-uppercase text-muted mb-3">Notes</h2>
            <div class="row g-3">
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$shift->description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('shifts.master.show', $shift) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection

@if(!$shift->hasOperationalUsage())
    @push('scripts')
    <script>
    (function () {
        function pad(value) {
            return value.toString().padStart(2, '0');
        }

        function formatMinutes(minutes) {
            if (minutes === null || isNaN(minutes)) {
                return '—';
            }
            var hours = Math.floor(minutes / 60);
            var remainder = minutes % 60;
            return hours + 'h ' + pad(remainder) + 'm';
        }

        function toMinutes(value) {
            var parts = (value || '').split(':');
            if (parts.length !== 2) {
                return null;
            }
            var hours = parseInt(parts[0], 10);
            var minutes = parseInt(parts[1], 10);
            if (isNaN(hours) || isNaN(minutes)) {
                return null;
            }
            return (hours * 60) + minutes;
        }

        function updatePreview() {
            var start = toMinutes(document.getElementsByName('start_time')[0].value);
            var end = toMinutes(document.getElementsByName('end_time')[0].value);
            var breakMinutes = parseInt(document.getElementsByName('break_duration_minutes')[0].value, 10) || 0;

            if (start === null || end === null) {
                return;
            }

            var overnight = end <= start;
            var gross = overnight ? ((24 * 60 - start) + end) : (end - start);
            var scheduled = gross - breakMinutes;

            document.getElementById('timing-preview-type').textContent = overnight ? 'Overnight Shift (crosses midnight)' : 'Same-Day Shift';
            document.getElementById('timing-preview-gross').textContent = formatMinutes(gross);
            document.getElementById('timing-preview-break').textContent = formatMinutes(breakMinutes);
            document.getElementById('timing-preview-work').textContent = scheduled >= 0 ? formatMinutes(scheduled) : 'Invalid (break exceeds gross duration)';
        }

        document.querySelectorAll('.shift-timing-input input').forEach(function (field) {
            field.addEventListener('input', updatePreview);
        });

        updatePreview();

        var overtimeBox = document.getElementsByName('overtime_applicable')[0];
        var overtimeMinutes = document.getElementsByName('overtime_start_after_minutes')[0];

        function toggleOvertimeField() {
            overtimeMinutes.disabled = ! overtimeBox.checked;
        }

        toggleOvertimeField();
        overtimeBox.addEventListener('change', toggleOvertimeField);
    })();
    </script>
    @endpush
@endif
