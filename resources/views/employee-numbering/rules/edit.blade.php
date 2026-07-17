@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Number Rules', 'url' => route('employee-numbering.rules.index')], ['label' => $rule->rule_name]]" />

    <x-page-header title="Edit Employee Number Rule" :subtitle="$rule->rule_name">
        <x-cancel-button href="{{ route('employee-numbering.rules.show', $rule) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    @if($rule->hasIssuedNumbers())
        <div class="alert alert-warning">
            This rule has already generated Employee Numbers. Create a new rule version to change its numbering format.
        </div>
    @endif

    <div class="page-surface p-4">
        <form method="post" action="{{ route('employee-numbering.rules.update', $rule) }}">
            @csrf
            @method('PUT')

            <h2 class="h6 text-uppercase text-muted mb-3">Rule Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><x-form.input name="rule_name" label="Rule Name" :value="$rule->rule_name" required /></div>
                <div class="col-md-6">
                    @if($rule->hasIssuedNumbers())
                        <label class="form-label">Employee Type</label>
                        <input type="text" class="form-control" value="{{ $rule->employeeType->name }}" disabled>
                    @else
                        <x-form.select name="employee_type_id" label="Employee Type" :options="$employeeTypes->pluck('name', 'id')" :value="$rule->employee_type_id" required />
                    @endif
                </div>
            </div>

            @if($rule->hasIssuedNumbers())
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Static Prefix</label>
                        <input type="text" class="form-control" value="{{ $rule->prefix ?? '-' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Separator</label>
                        <input type="text" class="form-control" value="{{ $rule->separator !== '' ? $rule->separator : 'No Separator' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Serial Number Length</label>
                        <input type="text" class="form-control" value="{{ $rule->serial_number_length }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reset Frequency</label>
                        <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $rule->reset_frequency)) }}" disabled>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Effective From</label>
                        <input type="text" class="form-control" value="{{ $rule->effective_from->format(config('hrms.date_format')) }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <x-form.input type="date" name="effective_to" label="Effective To" :value="$rule->effective_to?->format('Y-m-d')" />
                    </div>
                </div>
            @else
                <h2 class="h6 text-uppercase text-muted mb-3">Number Components</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><x-form.input name="prefix" label="Static Prefix" :value="$rule->prefix" /></div>
                    <div class="col-md-4 d-flex align-items-end">
                        <x-form.checkbox name="include_branch_code" label="Include Branch Code" :checked="$rule->include_branch_code" />
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <x-form.checkbox name="include_employee_type_prefix" label="Include Employee Type Prefix" :checked="$rule->include_employee_type_prefix" />
                    </div>
                    <div class="col-md-4"><x-form.input name="employee_type_prefix" label="Employee Type Prefix" :value="$rule->employee_type_prefix" /></div>
                    <div class="col-md-4 d-flex align-items-end">
                        <x-form.checkbox name="include_year" label="Include Year" :checked="$rule->include_year" />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="year_format" label="Year Format" :options="['YYYY' => 'Four Digit (YYYY)', 'YY' => 'Two Digit (YY)']" :value="$rule->year_format" />
                    </div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Serial Configuration</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <x-form.select name="separator" label="Separator" :options="['-' => 'Hyphen (-)', '/' => 'Slash (/)', '_' => 'Underscore (_)', 'none' => 'No Separator']" :value="$rule->separator === '' ? 'none' : $rule->separator" required />
                    </div>
                    <div class="col-md-3"><x-form.input type="number" name="serial_number_length" label="Serial Number Length" :value="$rule->serial_number_length" min="3" max="10" required /></div>
                    <div class="col-md-3"><x-form.input type="number" name="starting_number" label="Starting Number" :value="$rule->starting_number" min="1" required /></div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Reset Configuration</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <x-form.select name="reset_frequency" label="Reset Frequency" :options="['never' => 'Never', 'yearly' => 'Yearly', 'financial_yearly' => 'Financial Yearly']" :value="$rule->reset_frequency" required />
                    </div>
                </div>

                <h2 class="h6 text-uppercase text-muted mb-3">Effective Period</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><x-form.input type="date" name="effective_from" label="Effective From" :value="$rule->effective_from?->format('Y-m-d')" required /></div>
                    <div class="col-md-3"><x-form.input type="date" name="effective_to" label="Effective To" :value="$rule->effective_to?->format('Y-m-d')" /></div>
                    <div class="col-md-3 d-flex align-items-end">
                        <x-form.checkbox name="is_default" label="Default rule for this Branch and Employee Type" :checked="$rule->is_default" />
                    </div>
                </div>
            @endif

            <h2 class="h6 text-uppercase text-muted mb-3">Notes</h2>
            <div class="row g-3">
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$rule->description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('employee-numbering.rules.show', $rule) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
