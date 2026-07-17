@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employee Type Master', 'url' => route('masters.employee-types.index')], ['label' => $employeeType->name]]" />

    <x-page-header title="Edit Employee Type" :subtitle="$employeeType->code.' — '.$employeeType->name">
        <x-cancel-button href="{{ route('masters.employee-types.show', $employeeType) }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.employee-types.update', $employeeType) }}" id="employee-type-edit-form">
            @csrf
            @method('PUT')

            <h2 class="h6 text-uppercase text-muted mb-3">Basic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control" value="{{ $employeeType->code }}" readonly disabled>
                    <div class="form-text">The internal code is a stable system identifier and cannot be changed.</div>
                </div>
                <div class="col-md-9"><x-form.input name="name" label="Employee Type Name" :value="$employeeType->name" required /></div>
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$employeeType->description" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Processing Applicability</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <x-form.checkbox name="attendance_applicable" label="Attendance Applicable" :checked="$employeeType->attendance_applicable" />
                    <div class="form-text">Employees under this type will participate in attendance processing.</div>
                </div>
                <div class="col-md-3">
                    <x-form.checkbox name="leave_applicable" label="Leave Applicable" :checked="$employeeType->leave_applicable" />
                    <div class="form-text">Employees under this type will be eligible for leave features.</div>
                </div>
                <div class="col-md-3">
                    <x-form.checkbox name="payroll_applicable" label="Payroll Applicable" :checked="$employeeType->payroll_applicable" />
                    <div class="form-text">Employees under this type will be included in payroll processing.</div>
                </div>
                <div class="col-md-3">
                    <x-form.checkbox name="overtime_applicable" label="Overtime Applicable" :checked="$employeeType->overtime_applicable" />
                    <div class="form-text">Overtime may be calculated for employees under this type.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contractor Required</label>
                    <div><x-boolean-badge :value="$employeeType->requires_contractor" /></div>
                    <div class="form-text">A Contractor must be selected during Employee Registration. Not editable for system records.</div>
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Shift and Numbering Defaults</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-form.select name="default_shift_type" label="Default Shift Type" :options="['fixed' => 'Fixed', 'rotational' => 'Rotational']" :value="$employeeType->default_shift_type" required />
                    <div class="form-text mt-n2">New employees will initially use this shift type unless an administrator changes it.</div>
                </div>
                <div class="col-md-4">
                    <x-form.input name="employee_number_prefix" label="Employee Number Prefix" :value="$employeeType->employee_number_prefix" placeholder="e.g. STF" />
                    <div class="form-text mt-n2">Suggested prefix only. Final numbering will be controlled by the future Employee Number Rule Engine.</div>
                </div>
                <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" :value="$employeeType->display_order" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Status</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$employeeType->status" required />
                    @if($employeeType->is_system)
                        <div class="form-text mt-n2">This is a mandatory system Employee Type and cannot currently be inactivated.</div>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('masters.employee-types.show', $employeeType) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            // Registered on the capture phase so this confirmation runs
            // before the global double-submit-prevention listener in the
            // admin layout — otherwise cancelling here would leave the
            // submit button permanently disabled.
            document.getElementById('employee-type-edit-form').addEventListener('submit', function (event) {
                if (!window.confirm('Save changes to this Employee Type configuration? These defaults are used by future Employee, Shift, Attendance, Leave, and Payroll modules.')) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);
        </script>
    @endpush
@endsection
