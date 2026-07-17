@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Type Master', 'url' => route('masters.employee-types.index')], ['label' => $employeeType->name]]" />

    <x-page-header :title="$employeeType->name" :subtitle="$employeeType->code">
        @can('update', $employeeType)
            <a href="{{ route('masters.employee-types.edit', $employeeType) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
    </x-page-header>

    <div class="page-surface p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <x-status-badge :status="$employeeType->status" />
            <x-boolean-badge :value="$employeeType->is_system" true-label="System Record" false-label="Custom Record" />
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Classification Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Code</dt>
                    <dd class="col-sm-6">{{ $employeeType->code }}</dd>
                    <dt class="col-sm-6">Description</dt>
                    <dd class="col-sm-6">{{ $employeeType->description ?? '-' }}</dd>
                    <dt class="col-sm-6">Contractor Required</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$employeeType->requires_contractor" /></dd>
                    <dt class="col-sm-6">Default Shift Type</dt>
                    <dd class="col-sm-6">{{ ucfirst($employeeType->default_shift_type) }}</dd>
                    <dt class="col-sm-6">Employee Number Prefix</dt>
                    <dd class="col-sm-6">{{ $employeeType->employee_number_prefix ?? '-' }}</dd>
                    <dt class="col-sm-6">Display Order</dt>
                    <dd class="col-sm-6">{{ $employeeType->display_order }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Processing Applicability</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Attendance Applicable</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$employeeType->attendance_applicable" /></dd>
                    <dt class="col-sm-6">Leave Applicable</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$employeeType->leave_applicable" /></dd>
                    <dt class="col-sm-6">Payroll Applicable</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$employeeType->payroll_applicable" /></dd>
                    <dt class="col-sm-6">Overtime Applicable</dt>
                    <dd class="col-sm-6"><x-boolean-badge :value="$employeeType->overtime_applicable" /></dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Record Information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Created By</dt>
                    <dd class="col-sm-6">{{ $employeeType->createdBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-6">Created At</dt>
                    <dd class="col-sm-6">{{ $employeeType->created_at?->format(config('hrms.date_format').' H:i') }}</dd>
                    <dt class="col-sm-6">Last Updated By</dt>
                    <dd class="col-sm-6">{{ $employeeType->updatedBy?->name ?? '-' }}</dd>
                    <dt class="col-sm-6">Last Updated At</dt>
                    <dd class="col-sm-6">{{ $employeeType->updated_at?->format(config('hrms.date_format').' H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-0">
        These settings will be used as defaults for future Employee Registration, Shift, Attendance, Leave, Contractor, and Payroll modules. They do not perform any processing on their own in this phase.
    </div>
@endsection
