@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employees', 'url' => route('employees.index')], ['label' => 'Review Registration']]" />

    <x-page-header title="Review Registration" :subtitle="$employee->display_name">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary">Back to Edit</a>
    </x-page-header>

    <x-branch-context-badge />

    @if($preview)
        <div class="p-3 rounded-3 bg-light border mb-4">
            <div class="small text-muted mb-1">Employee Number Preview <span class="badge bg-secondary">Preview only — does not consume a serial</span></div>
            <div class="fs-4 fw-semibold">{{ $preview['preview'] }}</div>
            <div class="small text-muted mt-1">The actual number may differ if another registration completes first. The final number is generated when you complete registration.</div>
        </div>
    @else
        <div class="alert alert-danger">No active Employee Number Rule applies to this Branch, Employee Type, and Date of Joining. Registration cannot be completed until a rule is activated.</div>
    @endif

    @if($warnings !== [])
        <div class="alert alert-warning">
            <strong>Possible duplicate Employee detected:</strong>
            <ul class="mb-0 mt-2">
                @foreach($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="page-surface p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Personal Details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Employee Type</dt>
                    <dd class="col-sm-7">{{ $employee->employeeType->name }}</dd>
                    <dt class="col-sm-5">Name</dt>
                    <dd class="col-sm-7">{{ $employee->display_name }}</dd>
                    <dt class="col-sm-5">Date of Birth</dt>
                    <dd class="col-sm-7">{{ $employee->date_of_birth?->format(config('hrms.date_format')) }}</dd>
                    <dt class="col-sm-5">Gender</dt>
                    <dd class="col-sm-7">{{ \App\Models\Employee::GENDERS[$employee->gender] ?? $employee->gender }}</dd>
                    <dt class="col-sm-5">Nationality</dt>
                    <dd class="col-sm-7">{{ $employee->nationality }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Contact</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Personal Mobile</dt>
                    <dd class="col-sm-7">{{ $employee->contact?->personal_mobile ?? '-' }}</dd>
                    <dt class="col-sm-5">Personal Email</dt>
                    <dd class="col-sm-7">{{ $employee->contact?->personal_email ?? '-' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Address</h2>
                <p class="mb-0">{{ $employee->addresses->firstWhere('address_type', 'current')?->formatted() ?? '-' }}</p>
            </div>
            <div class="col-md-6">
                <h2 class="h6 text-uppercase text-muted mb-3">Employment</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Date of Joining</dt>
                    <dd class="col-sm-7">{{ $employee->date_of_joining?->format(config('hrms.date_format')) }}</dd>
                    <dt class="col-sm-5">Department</dt>
                    <dd class="col-sm-7">{{ $employee->department?->department_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Section</dt>
                    <dd class="col-sm-7">{{ $employee->section?->section_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Designation</dt>
                    <dd class="col-sm-7">{{ $employee->designation?->designation_name ?? '-' }}</dd>
                    <dt class="col-sm-5">Reporting Manager</dt>
                    <dd class="col-sm-7">{{ $employee->reportingManager?->display_name ?? 'None' }}</dd>
                </dl>

                @if($employee->employeeType->requires_contractor)
                    <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Contractor</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Contractor</dt>
                        <dd class="col-sm-7">{{ $employee->contractor?->legal_name ?? '-' }}</dd>
                        <dt class="col-sm-5">Engagement</dt>
                        <dd class="col-sm-7">{{ $employee->contractorBranchEngagement?->agreement_number ?? '-' }}</dd>
                    </dl>
                @endif

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Shift</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Shift Type</dt>
                    <dd class="col-sm-7">{{ ucfirst($employee->shift_type) }}</dd>
                    <dt class="col-sm-5">Fixed Shift</dt>
                    <dd class="col-sm-7">{{ $employee->fixedShift?->shift_name ?? '-' }}</dd>
                </dl>

                <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Emergency Contacts</h2>
                <p class="mb-0">{{ $employee->emergencyContacts->count() }} contact(s) on file.</p>
            </div>
        </div>
    </div>

    @can('completeRegistration', $employee)
        <div class="page-surface p-4">
            <form method="post" action="{{ route('employees.complete-registration', $employee) }}">
                @csrf
                @if($warnings !== [])
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="duplicate_warning_acknowledged" value="1" id="duplicate_warning_acknowledged" required>
                        <label class="form-check-label" for="duplicate_warning_acknowledged">I have reviewed the duplicate warning above and confirm this is a distinct Employee.</label>
                    </div>
                @endif
                <x-submit-button label="Complete Registration" :disabled="!$preview" />
            </form>
        </div>
    @endcan
@endsection
