@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Rules', 'url' => route('employee-numbering.rules.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Employee Number Rule" subtitle="Define a numbering format for the active branch.">
        <x-cancel-button href="{{ route('employee-numbering.rules.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('employee-numbering.rules.store') }}" id="rule-form">
            @csrf

            <h2 class="h6 text-uppercase text-muted mb-3">Rule Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><x-form.input name="rule_name" label="Rule Name" placeholder="e.g. Staff Numbering - Head Office" required /></div>
                <div class="col-md-6">
                    <x-form.select name="employee_type_id" label="Employee Type" :options="$employeeTypes->pluck('name', 'id')" required>
                        <option value="">Select an Employee Type</option>
                    </x-form.select>
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Number Components</h2>
            <div class="row g-3 mb-4 rule-preview-input">
                <div class="col-md-4"><x-form.input name="prefix" label="Static Prefix" placeholder="e.g. STF" /></div>
                <div class="col-md-4 d-flex align-items-end">
                    <x-form.checkbox name="include_branch_code" label="Include Branch Code" />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <x-form.checkbox name="include_employee_type_prefix" label="Include Employee Type Prefix" />
                </div>
                <div class="col-md-4" id="employee_type_prefix_wrapper">
                    <x-form.input name="employee_type_prefix" label="Employee Type Prefix" placeholder="Defaults from Employee Type Master" />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <x-form.checkbox name="include_year" label="Include Year" />
                </div>
                <div class="col-md-4" id="year_format_wrapper">
                    <x-form.select name="year_format" label="Year Format" :options="['YYYY' => 'Four Digit (YYYY)', 'YY' => 'Two Digit (YY)']" value="YYYY" />
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Serial Configuration</h2>
            <div class="row g-3 mb-4 rule-preview-input">
                <div class="col-md-3">
                    <x-form.select name="separator" label="Separator" :options="['-' => 'Hyphen (-)', '/' => 'Slash (/)', '_' => 'Underscore (_)', 'none' => 'No Separator']" value="-" required />
                </div>
                <div class="col-md-3"><x-form.input type="number" name="serial_number_length" label="Serial Number Length" value="4" min="3" max="10" required /></div>
                <div class="col-md-3"><x-form.input type="number" name="starting_number" label="Starting Number" value="1" min="1" required /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Reset Configuration</h2>
            <div class="row g-3 mb-4 rule-preview-input">
                <div class="col-md-6">
                    <x-form.select name="reset_frequency" label="Reset Frequency" :options="['never' => 'Never', 'yearly' => 'Yearly', 'financial_yearly' => 'Financial Yearly']" value="never" required />
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" value="{{ now()->toDateString() }}" required /></div>
                <div class="col-md-3"><x-form.input name="effective_to" type="date" label="Effective To" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Preview</h2>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="p-3 rounded-3 bg-light border">
                        <div class="small text-muted mb-1">Preview Employee Number <span class="badge bg-secondary">Preview only — does not consume a serial</span></div>
                        <div class="fs-4 fw-semibold" id="preview-output">—</div>
                        <div class="small text-muted mt-2" id="preview-breakdown"></div>
                        <div class="small text-muted mt-1" id="preview-period"></div>
                    </div>
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Status and Notes</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['draft' => 'Draft', 'inactive' => 'Inactive']" value="draft" required />
                    <div class="form-text mb-0">Use the Activate action on the rule after creation to make it Active.</div>
                </div>
                <div class="col-md-9"><x-form.textarea name="description" label="Description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Rule" />
                <x-cancel-button href="{{ route('employee-numbering.rules.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('rule-form');
    const previewUrl = @json(route('employee-numbering.rules.preview'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const employeeTypePrefixWrapper = document.getElementById('employee_type_prefix_wrapper');
    const yearFormatWrapper = document.getElementById('year_format_wrapper');
    const includeTypePrefixBox = document.getElementById('include_employee_type_prefix');
    const includeYearBox = document.getElementById('include_year');

    function toggleVisibility() {
        employeeTypePrefixWrapper.style.display = includeTypePrefixBox.checked ? '' : 'none';
        yearFormatWrapper.style.display = includeYearBox.checked ? '' : 'none';
    }

    toggleVisibility();
    includeTypePrefixBox.addEventListener('change', toggleVisibility);
    includeYearBox.addEventListener('change', toggleVisibility);

    let debounceTimer = null;

    function requestPreview() {
        const employeeTypeId = document.getElementById('employee_type_id').value;

        if (! employeeTypeId) {
            document.getElementById('preview-output').textContent = 'Select an Employee Type to preview.';
            document.getElementById('preview-breakdown').textContent = '';
            document.getElementById('preview-period').textContent = '';
            return;
        }

        const payload = {
            employee_type_id: employeeTypeId,
            prefix: document.getElementById('prefix').value,
            include_branch_code: document.getElementById('include_branch_code').checked ? 1 : 0,
            include_employee_type_prefix: includeTypePrefixBox.checked ? 1 : 0,
            employee_type_prefix: document.getElementById('employee_type_prefix').value,
            include_year: includeYearBox.checked ? 1 : 0,
            year_format: document.getElementById('year_format').value,
            separator: document.getElementById('separator').value,
            serial_number_length: document.getElementById('serial_number_length').value,
            starting_number: document.getElementById('starting_number').value,
            reset_frequency: document.getElementById('reset_frequency').value,
            effective_from: document.getElementById('effective_from').value,
        };

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((data) => {
                document.getElementById('preview-output').textContent = data.preview || '—';
                document.getElementById('preview-breakdown').textContent = 'Components: ' + data.components.join(' | ');
                document.getElementById('preview-period').textContent = 'Sequence period: ' + data.sequence_period;
            })
            .catch(() => {
                document.getElementById('preview-output').textContent = 'Unable to build a preview with the current values.';
                document.getElementById('preview-breakdown').textContent = '';
                document.getElementById('preview-period').textContent = '';
            });
    }

    form.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(requestPreview, 300);
        });
        field.addEventListener('change', requestPreview);
    });
})();
</script>
@endpush
