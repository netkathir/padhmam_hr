@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Employees', 'url' => route('employees.index')], ['label' => $employee->display_name]]" />

    <x-page-header :title="$employee->display_name" :subtitle="$employee->employee_number ?? 'Draft — no Employee Number yet'">
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @if($employee->isDraft())
            @can('completeRegistration', $employee)
                <a href="{{ route('employees.review', $employee) }}" class="btn btn-primary">Review &amp; Complete Registration</a>
            @endcan
        @endif
        @can('activate', $employee)
            @if($employee->isInactive())
                <form method="post" action="{{ route('employees.activate', $employee) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Activate</button>
                </form>
            @endif
        @endcan
        @can('inactivate', $employee)
            @if($employee->isActive())
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#inactivateEmployeeModal">Inactivate</button>
            @endif
        @endcan
        @can('reactivate', $employee)
            @if($employee->isInactive())
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#reactivateEmployeeModal">Reactivate</button>
            @endif
        @endcan
        @can('separate', $employee)
            @if(in_array($employee->status, ['active', 'inactive']))
                <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#separateEmployeeModal">Separate</button>
            @endif
        @endcan
    </x-page-header>

    <x-branch-context-badge />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <x-status-badge :status="$employee->status" />
        <span class="badge bg-secondary-subtle text-dark border">{{ $employee->employeeType->name }}</span>
        <span class="badge {{ $employee->usesFixedShift() ? 'bg-primary' : 'bg-info text-dark' }}">{{ ucfirst($employee->shift_type) }} Shift</span>
    </div>

    <div class="page-surface p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-2 text-center">
                @if($employee->photoUrl())
                    <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->display_name }}" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center mx-auto mb-2" style="width:100px;height:100px;">
                        <i class="bi bi-person fs-1 text-muted"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-10">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Branch</div><div class="fw-semibold">{{ $employee->branch->branch_name }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Department</div><div class="fw-semibold">{{ $employee->department?->department_name ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Section</div><div class="fw-semibold">{{ $employee->section?->section_name ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Designation</div><div class="fw-semibold">{{ $employee->designation?->designation_name ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Reporting Manager</div><div class="fw-semibold">{{ $employee->reportingManager?->display_name ?? '-' }}</div></div>
                    <div class="col-md-3"><div class="text-muted small">Date of Joining</div><div class="fw-semibold">{{ $employee->date_of_joining?->format(config('hrms.date_format')) }}</div></div>
                    @if($employee->isContractLabour())
                        <div class="col-md-3"><div class="text-muted small">Contractor</div><div class="fw-semibold">{{ $employee->contractor?->legal_name ?? '-' }}</div></div>
                    @endif
                    <div class="col-md-3"><div class="text-muted small">Fixed Shift</div><div class="fw-semibold">{{ $employee->fixedShift?->shift_name ?? ($employee->usesRotationalShift() ? 'Assigned manually' : '-') }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button">Personal</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employment" type="button">Employment</button></li>
        @if($employee->isContractLabour())
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contractor" type="button">Contractor</button></li>
        @endif
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shift" type="button">Shift</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button">Contact and Address</button></li>
        @can('viewSensitive', $employee)
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button">Bank</button></li>
        @endcan
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-emergency" type="button">Emergency Contacts</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">Documents</button></li>
        @can('viewHistory', $employee)
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">Change History</button></li>
        @endcan
    </ul>

    <div class="tab-content page-surface p-4">
        <div class="tab-pane fade show active" id="tab-personal">
            <dl class="row mb-0">
                <dt class="col-sm-3">Date of Birth</dt><dd class="col-sm-9">{{ $employee->date_of_birth?->format(config('hrms.date_format')) }}</dd>
                <dt class="col-sm-3">Gender</dt><dd class="col-sm-9">{{ \App\Models\Employee::GENDERS[$employee->gender] ?? '-' }}</dd>
                <dt class="col-sm-3">Marital Status</dt><dd class="col-sm-9">{{ \App\Models\Employee::MARITAL_STATUSES[$employee->marital_status] ?? '-' }}</dd>
                <dt class="col-sm-3">Blood Group</dt><dd class="col-sm-9">{{ $employee->blood_group ?? '-' }}</dd>
                <dt class="col-sm-3">Nationality</dt><dd class="col-sm-9">{{ $employee->nationality }}</dd>
            </dl>
        </div>

        <div class="tab-pane fade" id="tab-employment">
            <dl class="row mb-0">
                <dt class="col-sm-3">Probation Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->probation_applicable" /></dd>
                <dt class="col-sm-3">Probation End Date</dt><dd class="col-sm-9">{{ $employee->probation_end_date?->format(config('hrms.date_format')) ?? '-' }}</dd>
                <dt class="col-sm-3">Confirmation Date</dt><dd class="col-sm-9">{{ $employee->confirmation_date?->format(config('hrms.date_format')) ?? '-' }}</dd>
                <dt class="col-sm-3">Biometric Identifier</dt><dd class="col-sm-9">{{ $employee->biometric_identifier ?? '-' }}</dd>
                <dt class="col-sm-3">Attendance Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->attendance_applicable" /></dd>
                <dt class="col-sm-3">Leave Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->leave_applicable" /></dd>
                <dt class="col-sm-3">Payroll Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->payroll_applicable" /></dd>
                <dt class="col-sm-3">Overtime Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->overtime_applicable" /></dd>
            </dl>
        </div>

        @if($employee->isContractLabour())
            <div class="tab-pane fade" id="tab-contractor">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Contractor</dt><dd class="col-sm-9">{{ $employee->contractor?->legal_name ?? '-' }}</dd>
                    <dt class="col-sm-3">Agreement Number</dt><dd class="col-sm-9">{{ $employee->contractorBranchEngagement?->agreement_number ?? '-' }}</dd>
                    <dt class="col-sm-3">Contract Period</dt><dd class="col-sm-9">{{ $employee->contractorBranchEngagement?->contract_start_date?->format(config('hrms.date_format')) }} &ndash; {{ $employee->contractorBranchEngagement?->contract_end_date?->format(config('hrms.date_format')) ?? 'Open' }}</dd>
                </dl>
            </div>
        @endif

        <div class="tab-pane fade" id="tab-shift">
            <dl class="row mb-0">
                <dt class="col-sm-3">Shift Type</dt><dd class="col-sm-9">{{ ucfirst($employee->shift_type) }}</dd>
                <dt class="col-sm-3">Fixed Shift</dt><dd class="col-sm-9">{{ $employee->fixedShift?->shift_name ?? ($employee->usesRotationalShift() ? 'Rotational Shifts are assigned manually after registration.' : '-') }}</dd>
                @if($employee->shift_type_override_reason)
                    <dt class="col-sm-3">Override Reason</dt><dd class="col-sm-9">{{ $employee->shift_type_override_reason }}</dd>
                @endif
            </dl>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 text-uppercase text-muted mb-0">Shift Assignment</h2>
                <div class="d-flex gap-2">
                    @can('viewHistory', \App\Models\EmployeeShiftAssignment::class)
                        <a href="{{ route('employee-shifts.history', $employee) }}" class="btn btn-sm btn-outline-secondary">View History</a>
                    @endcan
                    @if($employee->currentShiftAssignment)
                        @if($employee->usesFixedShift())
                            @can('change', \App\Models\EmployeeShiftAssignment::class)
                                <a href="{{ route('employee-shifts.change.create', $employee) }}" class="btn btn-sm btn-outline-primary">Change Fixed Shift</a>
                            @endcan
                        @endif
                        @can('temporary', \App\Models\EmployeeShiftAssignment::class)
                            <a href="{{ route('employee-shifts.temporary.create', $employee) }}" class="btn btn-sm btn-outline-primary">Assign Temporary Shift</a>
                        @endcan
                    @else
                        @can('create', \App\Models\EmployeeShiftAssignment::class)
                            <a href="{{ route('employee-shifts.create', $employee) }}" class="btn btn-sm btn-primary">Assign Shift</a>
                        @endcan
                    @endif
                </div>
            </div>

            @if($employee->currentShiftAssignment)
                <dl class="row mb-0">
                    <dt class="col-sm-3">Current Shift</dt>
                    <dd class="col-sm-9">
                        {{ $employee->currentShiftAssignment->shift?->shift_name }}
                        <span class="badge bg-secondary-subtle text-dark border">{{ ucfirst($employee->currentShiftAssignment->assignment_type) }}</span>
                    </dd>
                    <dt class="col-sm-3">Effective From</dt>
                    <dd class="col-sm-9">{{ $employee->currentShiftAssignment->effective_from?->format(config('hrms.date_format')) }}</dd>
                    @if($employee->currentShiftAssignment->effective_to)
                        <dt class="col-sm-3">Effective To</dt>
                        <dd class="col-sm-9">{{ $employee->currentShiftAssignment->effective_to->format(config('hrms.date_format')) }}</dd>
                    @endif
                </dl>
            @else
                <x-empty-state title="Shift Assignment Pending" message="This Employee has no current Shift assignment. Use Assign Shift to create one." />
            @endif

            @if($employee->scheduledShiftAssignments->isNotEmpty())
                <h2 class="h6 text-uppercase text-muted mt-4 mb-3">Upcoming Scheduled Assignments</h2>
                <x-data-table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Shift</th>
                        <th>Type</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($employee->scheduledShiftAssignments as $scheduled)
                        <tr>
                            <td>{{ $scheduled->shift?->shift_name }}</td>
                            <td>{{ ucfirst($scheduled->assignment_type) }}</td>
                            <td>{{ $scheduled->effective_from?->format(config('hrms.date_format')) }}</td>
                            <td>{{ $scheduled->effective_to?->format(config('hrms.date_format')) ?? 'Open' }}</td>
                            <td class="text-end">
                                <a href="{{ route('employee-shifts.show', $scheduled) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </x-data-table>
            @endif
        </div>

        <div class="tab-pane fade" id="tab-contact">
            <h2 class="h6 text-uppercase text-muted mb-3">Contact</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Personal Mobile</dt><dd class="col-sm-9">{{ $employee->contact?->personal_mobile ?? '-' }}</dd>
                <dt class="col-sm-3">Alternate Mobile</dt><dd class="col-sm-9">{{ $employee->contact?->alternate_mobile ?? '-' }}</dd>
                <dt class="col-sm-3">Personal Email</dt><dd class="col-sm-9">{{ $employee->contact?->personal_email ?? '-' }}</dd>
                <dt class="col-sm-3">Official Email</dt><dd class="col-sm-9">{{ $employee->contact?->official_email ?? '-' }}</dd>
            </dl>
            <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Current Address</h2>
            <p>{{ $employee->addresses->firstWhere('address_type', 'current')?->formatted() ?? '-' }}</p>
            <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Permanent Address</h2>
            <p class="mb-0">{{ $employee->addresses->firstWhere('address_type', 'permanent')?->formatted() ?? '-' }}</p>
        </div>

        @can('viewSensitive', $employee)
            <div class="tab-pane fade" id="tab-statutory">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Aadhaar Number</dt><dd class="col-sm-9">{{ $employee->maskedAadhaar() ?? '-' }}</dd>
                    <dt class="col-sm-3">PAN</dt><dd class="col-sm-9">{{ $employee->statutoryDetail?->maskedPan() ?? '-' }}</dd>
                    <dt class="col-sm-3">UAN Number</dt><dd class="col-sm-9">{{ $employee->statutoryDetail?->uan_number ?? '-' }}</dd>
                    <dt class="col-sm-3">PF Number</dt><dd class="col-sm-9">{{ $employee->statutoryDetail?->pf_number ?? '-' }}</dd>
                    <dt class="col-sm-3">ESI Number</dt><dd class="col-sm-9">{{ $employee->statutoryDetail?->esi_number ?? '-' }}</dd>
                    <dt class="col-sm-3">PF Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->statutoryDetail?->pf_applicable ?? true" /></dd>
                    <dt class="col-sm-3">ESI Applicable</dt><dd class="col-sm-9"><x-boolean-badge :value="$employee->statutoryDetail?->esi_applicable ?? true" /></dd>
                </dl>
            </div>

            <div class="tab-pane fade" id="tab-bank">
                @php($primaryBank = $employee->bankAccounts->firstWhere('is_primary', true))
                <dl class="row mb-0">
                    <dt class="col-sm-3">Account Holder</dt><dd class="col-sm-9">{{ $primaryBank?->account_holder_name ?? '-' }}</dd>
                    <dt class="col-sm-3">Bank Name</dt><dd class="col-sm-9">{{ $primaryBank?->bank_name ?? '-' }}</dd>
                    <dt class="col-sm-3">Account Number</dt><dd class="col-sm-9">{{ $primaryBank?->maskedAccountNumber() ?? '-' }}</dd>
                    <dt class="col-sm-3">IFSC Code</dt><dd class="col-sm-9">{{ $primaryBank?->ifsc_code ?? '-' }}</dd>
                </dl>
            </div>
        @endcan

        <div class="tab-pane fade" id="tab-emergency">
            <x-data-table class="table mb-0">
                <thead><tr><th>Name</th><th>Relationship</th><th>Primary Phone</th><th>Primary</th></tr></thead>
                <tbody>
                @forelse($employee->emergencyContacts as $contact)
                    <tr>
                        <td>{{ $contact->name }}</td>
                        <td>{{ $contact->relationship }}</td>
                        <td>{{ $contact->primary_phone }}</td>
                        <td><x-boolean-badge :value="$contact->is_primary" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="No emergency contacts" message="No emergency contacts have been added yet." /></td></tr>
                @endforelse
                </tbody>
            </x-data-table>
        </div>

        <div class="tab-pane fade" id="tab-documents">
            <x-data-table class="table mb-3">
                <thead><tr><th>Document Type</th><th>Number</th><th>Expiry</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($employee->documents as $document)
                    <tr>
                        <td>{{ $document->typeLabel() }}</td>
                        <td>{{ $document->document_number ?? '-' }}</td>
                        <td>{{ $document->expiry_date?->format(config('hrms.date_format')) ?? '-' }}</td>
                        <td><x-status-badge :status="$document->status" /></td>
                        <td class="text-end">
                            @can('download', $document)
                                <a href="{{ route('employees.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                            @endcan
                            @can('upload', [\App\Models\EmployeeDocument::class, $employee])
                                @if($document->isActive())
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#replaceDocumentModal{{ $document->id }}">Replace</button>
                                    <div class="modal fade" id="replaceDocumentModal{{ $document->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="{{ route('employees.documents.replace', $document) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Replace Document</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label class="form-label">New File <span class="text-danger">*</span></label>
                                                        <input type="file" name="file" class="form-control" required accept=".pdf,.png,.jpg,.jpeg,.webp">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Replace</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endcan
                            @can('inactivate', $document)
                                @if($document->isActive())
                                    <form method="post" action="{{ route('employees.documents.inactivate', $document) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Inactivate</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No documents yet" message="Upload compliance documents for this Employee." /></td></tr>
                @endforelse
                </tbody>
            </x-data-table>

            @can('upload', [\App\Models\EmployeeDocument::class, $employee])
                <form method="post" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <x-form.select name="document_type" label="Document Type" :options="$documentTypes" required />
                        </div>
                        <div class="col-md-3"><x-form.input name="document_number" label="Document Number" /></div>
                        <div class="col-md-2"><x-form.input type="date" name="issued_date" label="Issued Date" /></div>
                        <div class="col-md-2"><x-form.input type="date" name="expiry_date" label="Expiry Date" /></div>
                        <div class="col-md-2">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required accept=".pdf,.png,.jpg,.jpeg,.webp">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-3"><x-submit-button label="Upload Document" /></div>
                </form>
            @endcan
        </div>

        @can('viewHistory', $employee)
            <div class="tab-pane fade" id="tab-history">
                <x-data-table class="table mb-0">
                    <thead><tr><th>Change Type</th><th>Reason</th><th>Changed By</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($employee->changeHistories as $history)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $history->change_type)) }}</td>
                            <td>{{ $history->reason ?? '-' }}</td>
                            <td>{{ $history->changedBy?->name ?? '-' }}</td>
                            <td>{{ $history->created_at?->format(config('hrms.date_format').' H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state title="No history yet" message="No significant changes have been recorded yet." /></td></tr>
                    @endforelse
                    </tbody>
                </x-data-table>
            </div>
        @endcan
    </div>

    @can('inactivate', $employee)
        <form id="inactivate-employee-form" method="post" action="{{ route('employees.inactivate', $employee) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <div class="modal fade" id="inactivateEmployeeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Inactivate Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="inactivate-effective-date">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="inactivate-effective-date" form="inactivate-employee-form" name="effective_date" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="inactivate-reason">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="inactivate-reason" form="inactivate-employee-form" name="reason" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="inactivate-employee-form" class="btn btn-danger">Inactivate</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('reactivate', $employee)
        <form id="reactivate-employee-form" method="post" action="{{ route('employees.reactivate', $employee) }}" class="d-none">
            @csrf
            @method('PATCH')
        </form>
        <div class="modal fade" id="reactivateEmployeeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Reactivate Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label" for="reactivate-reason">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reactivate-reason" form="reactivate-employee-form" name="reason" rows="2" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="reactivate-employee-form" class="btn btn-success">Reactivate</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('separate', $employee)
        <form id="separate-employee-form" method="post" action="{{ route('employees.separate', $employee) }}" class="d-none">
            @csrf
        </form>
        <div class="modal fade" id="separateEmployeeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Separate Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="separation-type">Separation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="separation-type" form="separate-employee-form" name="separation_type" required>
                                @foreach(\App\Models\EmployeeSeparation::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="last-working-date">Last Working Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="last-working-date" form="separate-employee-form" name="last_working_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notice-date">Notice Date</label>
                            <input type="date" class="form-control" id="notice-date" form="separate-employee-form" name="notice_date">
                        </div>
                        <div class="mb-0">
                            <label class="form-label" for="separation-reason">Separation Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="separation-reason" form="separate-employee-form" name="separation_reason" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="separate-employee-form" class="btn btn-dark">Confirm Separation</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
