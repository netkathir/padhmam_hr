@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Shift Master', 'url' => route('shifts.master.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Shift" subtitle="Define a new Shift for the active branch.">
        <x-cancel-button href="{{ route('shifts.master.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('shifts.master.store') }}" id="shift-form">
            @csrf

            <h2 class="h6 text-uppercase text-muted mb-3">Basic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input name="shift_code" label="Shift Code" placeholder="e.g. GEN" required /></div>
                <div class="col-md-5"><x-form.input name="shift_name" label="Shift Name" required /></div>
                <div class="col-md-4"><x-form.input name="short_name" label="Short Name" /></div>
                <div class="col-md-4">
                    <x-form.select name="shift_type" label="Shift Type" :options="['fixed' => 'Fixed', 'rotational' => 'Rotational']" value="fixed" required />
                </div>
                <div class="col-md-4"><x-form.input name="color_code" label="Colour" type="color" value="#1F6FEB" /></div>
                <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" value="0" min="0" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Shift Timing</h2>
            <div class="row g-3 mb-2 shift-timing-input">
                <div class="col-md-3"><x-form.input type="time" name="start_time" label="Start Time" value="09:00" required /></div>
                <div class="col-md-3"><x-form.input type="time" name="end_time" label="End Time" value="18:00" required /></div>
                <div class="col-md-3"><x-form.input type="number" name="break_duration_minutes" label="Break Duration (minutes)" value="60" min="0" required /></div>
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
                <div class="col-md-3"><x-form.input type="number" name="early_entry_allowed_minutes" label="Early Entry Allowed (minutes)" value="0" min="0" required /></div>
                <div class="col-md-3"><x-form.input type="number" name="late_entry_grace_minutes" label="Late Entry Grace (minutes)" value="0" min="0" required /></div>
                <div class="col-md-3"><x-form.input type="number" name="early_exit_grace_minutes" label="Early Exit Grace (minutes)" value="0" min="0" required /></div>
                <div class="col-md-3"><x-form.input type="number" name="late_exit_allowed_minutes" label="Late Exit Allowed (minutes)" value="0" min="0" required /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Attendance Thresholds</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input type="number" name="minimum_half_day_minutes" label="Minimum Half-Day Minutes" min="1" /></div>
                <div class="col-md-4"><x-form.input type="number" name="minimum_full_day_minutes" label="Minimum Full-Day Minutes" min="1" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Overtime Configuration</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4 d-flex align-items-end">
                    <x-form.checkbox name="overtime_applicable" label="Overtime Applicable" />
                </div>
                <div class="col-md-4"><x-form.input type="number" name="overtime_start_after_minutes" label="Overtime Start After Minutes" min="0" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Employee Type Compatibility</h2>
            <div class="row g-3 mb-4">
                <div class="col-12 d-flex flex-wrap gap-3">
                    @foreach($employeeTypes as $employeeType)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="employee_type_ids[]" value="{{ $employeeType->id }}" id="employee_type_{{ $employeeType->id }}">
                            <label class="form-check-label" for="employee_type_{{ $employeeType->id }}">{{ $employeeType->name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('employee_type_ids')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Applicable Days</h2>
            <div class="row g-3 mb-4">
                <div class="col-12 d-flex flex-wrap gap-3">
                    @foreach(config('hrms.shift_day_codes') as $code => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="applicable_days[]" value="{{ $code }}" id="day_{{ $code }}" checked>
                            <label class="form-check-label" for="day_{{ $code }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                @error('applicable_days')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" value="{{ now()->toDateString() }}" required /></div>
                <div class="col-md-3"><x-form.input type="date" name="effective_to" label="Effective To" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Status and Notes</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['draft' => 'Draft', 'inactive' => 'Inactive']" value="draft" required />
                    <div class="form-text mb-0">Use the Activate action on the Shift after creation to make it Active.</div>
                </div>
                <div class="col-md-9"><x-form.textarea name="description" label="Description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Shift" />
                <x-cancel-button href="{{ route('shifts.master.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection

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
            document.getElementById('timing-preview-type').textContent = '—';
            document.getElementById('timing-preview-gross').textContent = '—';
            document.getElementById('timing-preview-break').textContent = '—';
            document.getElementById('timing-preview-work').textContent = '—';
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
        if (! overtimeBox.checked) {
            overtimeMinutes.value = '';
        }
    }

    toggleOvertimeField();
    overtimeBox.addEventListener('change', toggleOvertimeField);
})();
</script>
@endpush
