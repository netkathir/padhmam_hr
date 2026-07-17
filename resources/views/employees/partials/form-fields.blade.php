@php
    $employee ??= null;
    $isEdit = $employee !== null;
    $contact = $employee?->contact;
    $currentAddress = $employee?->addresses->firstWhere('address_type', 'current');
    $permanentAddress = $employee?->addresses->firstWhere('address_type', 'permanent');
    $statutory = $employee?->statutoryDetail;
    $bank = $employee?->bankAccounts->firstWhere('is_primary', true);
    $emergencyContacts = $employee?->emergencyContacts ?? collect();
    $canEditSensitive = auth()->user()?->can('editSensitive', $employee ?? \App\Models\Employee::class) ?? auth()->user()?->hasPermissionTo('employee.edit-sensitive');
    $employeeTypeMeta = $employeeTypes->mapWithKeys(fn ($type) => [$type->id => [
        'requires_contractor' => (bool) $type->requires_contractor,
        'default_shift_type' => $type->default_shift_type,
        'attendance_applicable' => (bool) $type->attendance_applicable,
        'leave_applicable' => (bool) $type->leave_applicable,
        'payroll_applicable' => (bool) $type->payroll_applicable,
        'overtime_applicable' => (bool) $type->overtime_applicable,
    ]]);
@endphp

<ul class="nav nav-tabs mb-4" id="employee-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basic Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button">Contact Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address" type="button">Address</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-employment" type="button">Employment Details</button></li>
    <li class="nav-item" id="tab-nav-contractor"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contractor" type="button">Contractor Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shift" type="button">Shift Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank" type="button">Bank Details</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-emergency" type="button">Emergency Contacts</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-basic">
        <div class="row g-3">
            <div class="col-md-4">
                <x-form.select name="employee_type_id" label="Employee Type" id="employee_type_id" :options="$employeeTypes->pluck('name', 'id')" :value="$employee?->employee_type_id" required>
                    <option value="">Select an Employee Type</option>
                </x-form.select>
            </div>
            @if($employee?->employee_number)
                <div class="col-md-4">
                    <label class="form-label">Employee Number</label>
                    <input type="text" class="form-control" value="{{ $employee->employee_number }}" disabled>
                </div>
            @endif
            <div class="col-md-4"><x-form.input name="first_name" label="First Name" :value="$employee?->first_name" required /></div>
            <div class="col-md-4"><x-form.input name="middle_name" label="Middle Name" :value="$employee?->middle_name" /></div>
            <div class="col-md-4"><x-form.input name="last_name" label="Last Name" :value="$employee?->last_name" /></div>
            <div class="col-md-4"><x-form.input type="date" name="date_of_birth" label="Date of Birth" :value="$employee?->date_of_birth?->format('Y-m-d')" required /></div>
            <div class="col-md-4">
                <x-form.select name="gender" label="Gender" :options="\App\Models\Employee::GENDERS" :value="$employee?->gender" required />
            </div>
            <div class="col-md-4">
                <x-form.select name="marital_status" label="Marital Status" :options="\App\Models\Employee::MARITAL_STATUSES" :value="$employee?->marital_status">
                    <option value="">Not specified</option>
                </x-form.select>
            </div>
            <div class="col-md-4">
                <x-form.select name="blood_group" label="Blood Group" :options="array_combine(\App\Models\Employee::BLOOD_GROUPS, \App\Models\Employee::BLOOD_GROUPS)" :value="$employee?->blood_group">
                    <option value="">Not specified</option>
                </x-form.select>
            </div>
            <div class="col-md-4"><x-form.input name="nationality" label="Nationality" :value="$employee?->nationality ?? 'India'" required /></div>
            <div class="col-md-6">
                <label class="form-label">Employee Photo</label>
                @if($employee?->photoUrl())
                    <div class="mb-2"><img src="{{ $employee->photoUrl() }}" alt="Current photo" style="height:64px;width:64px;object-fit:cover;border-radius:50%;"></div>
                @endif
                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept=".png,.jpg,.jpeg,.webp">
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-contact">
        <div class="row g-3">
            <div class="col-md-4"><x-form.input name="contact[personal_mobile]" label="Personal Mobile" :value="$contact?->personal_mobile" required /></div>
            <div class="col-md-4"><x-form.input name="contact[alternate_mobile]" label="Alternate Mobile" :value="$contact?->alternate_mobile" /></div>
            <div class="col-md-4"><x-form.input type="email" name="contact[personal_email]" label="Personal Email" :value="$contact?->personal_email" /></div>
            <div class="col-md-4"><x-form.input type="email" name="contact[official_email]" label="Official Email" :value="$contact?->official_email" /></div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-address">
        <h3 class="h6 text-uppercase text-muted mb-3">Current Address</h3>
        <div class="row g-3 mb-4">
            <div class="col-12"><x-form.input name="addresses[current][address_line_1]" label="Address Line 1" :value="$currentAddress?->address_line_1" required /></div>
            <div class="col-12"><x-form.input name="addresses[current][address_line_2]" label="Address Line 2" :value="$currentAddress?->address_line_2" /></div>
            <div class="col-md-3"><x-form.input name="addresses[current][city]" label="City" :value="$currentAddress?->city" required /></div>
            <div class="col-md-3"><x-form.input name="addresses[current][district]" label="District" :value="$currentAddress?->district" /></div>
            <div class="col-md-3"><x-form.input name="addresses[current][state]" label="State" :value="$currentAddress?->state" required /></div>
            <div class="col-md-3"><x-form.input name="addresses[current][postal_code]" label="Postal Code" :value="$currentAddress?->postal_code" required /></div>
            <div class="col-md-4"><x-form.input name="addresses[current][country]" label="Country" :value="$currentAddress?->country ?? 'India'" required /></div>
        </div>
        <h3 class="h6 text-uppercase text-muted mb-3">Permanent Address</h3>
        <div class="row g-3">
            <div class="col-12">
                <x-form.checkbox name="addresses[permanent][is_same_as_current]" id="permanent_same_as_current" label="Permanent Address is same as Current Address" :checked="$permanentAddress?->is_same_as_current ?? false" />
            </div>
            <div id="permanent-address-fields" class="row g-3">
                <div class="col-12"><x-form.input name="addresses[permanent][address_line_1]" label="Address Line 1" :value="$permanentAddress?->address_line_1" /></div>
                <div class="col-12"><x-form.input name="addresses[permanent][address_line_2]" label="Address Line 2" :value="$permanentAddress?->address_line_2" /></div>
                <div class="col-md-3"><x-form.input name="addresses[permanent][city]" label="City" :value="$permanentAddress?->city" /></div>
                <div class="col-md-3"><x-form.input name="addresses[permanent][district]" label="District" :value="$permanentAddress?->district" /></div>
                <div class="col-md-3"><x-form.input name="addresses[permanent][state]" label="State" :value="$permanentAddress?->state" /></div>
                <div class="col-md-3"><x-form.input name="addresses[permanent][postal_code]" label="Postal Code" :value="$permanentAddress?->postal_code" /></div>
                <div class="col-md-4"><x-form.input name="addresses[permanent][country]" label="Country" :value="$permanentAddress?->country ?? 'India'" /></div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-employment">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><x-form.input type="date" name="date_of_joining" label="Date of Joining" id="date_of_joining" :value="$employee?->date_of_joining?->format('Y-m-d')" required /></div>
            <div class="col-md-3">
                <x-form.select name="department_id" label="Department" id="department_id" :options="$departments->pluck('department_name', 'id')" :value="$employee?->department_id" required>
                    <option value="">Select a Department</option>
                </x-form.select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Section</label>
                <select name="section_id" id="section_id" class="form-select">
                    <option value="">Select a Section</option>
                    @if($employee?->section)
                        <option value="{{ $employee->section->id }}" selected>{{ $employee->section->section_name }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Designation <span class="text-danger">*</span></label>
                <select name="designation_id" id="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                    <option value="">Select a Designation</option>
                    @if($employee?->designation)
                        <option value="{{ $employee->designation->id }}" selected>{{ $employee->designation->designation_name }}</option>
                    @endif
                </select>
                @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Reporting Manager</label>
                <select name="reporting_manager_id" id="reporting_manager_id" class="form-select">
                    <option value="">None</option>
                    @if($employee?->reportingManager)
                        <option value="{{ $employee->reportingManager->id }}" selected>{{ $employee->reportingManager->display_name }} ({{ $employee->reportingManager->employee_number }})</option>
                    @endif
                    @foreach($reportingManagerCandidates as $candidate)
                        @if(!$employee?->reportingManager || $candidate->id !== $employee->reportingManager->id)
                            <option value="{{ $candidate->id }}">{{ $candidate->display_name }} ({{ $candidate->employee_number ?? 'No number' }})</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 d-flex align-items-end">
                <x-form.checkbox name="probation_applicable" label="Probation Applicable" :checked="$employee?->probation_applicable ?? false" />
            </div>
            <div class="col-md-3"><x-form.input type="number" name="probation_period_days" label="Probation Period (days)" :value="$employee?->probation_period_days" min="1" /></div>
            <div class="col-md-3"><x-form.input type="date" name="probation_end_date" label="Probation End Date" :value="$employee?->probation_end_date?->format('Y-m-d')" /></div>
            <div class="col-md-3"><x-form.input type="date" name="confirmation_date" label="Confirmation Date" :value="$employee?->confirmation_date?->format('Y-m-d')" /></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><x-form.input name="biometric_identifier" label="Biometric Identifier" :value="$employee?->biometric_identifier" /></div>
        </div>

        <h3 class="h6 text-uppercase text-muted mb-3">Applicability @if($canEditSensitive)<span class="badge bg-secondary">Overridable</span>@endif</h3>
        <div class="row g-3 mb-3">
            @php
                $applicabilityFields = [
                    'attendance_applicable' => ['label' => 'Attendance Applicable', 'default' => true],
                    'leave_applicable' => ['label' => 'Leave Applicable', 'default' => true],
                    'payroll_applicable' => ['label' => 'Payroll Applicable', 'default' => true],
                    'overtime_applicable' => ['label' => 'Overtime Applicable', 'default' => false],
                ];
            @endphp
            @foreach($applicabilityFields as $fieldName => $field)
                <div class="col-md-3">
                    @if($canEditSensitive)
                        <x-form.checkbox :name="$fieldName" :label="$field['label']" :checked="$employee?->{$fieldName} ?? $field['default']" />
                    @else
                        {{-- Disabled checkboxes never submit a value, so a
                        parallel hidden input carries the unchanged current
                        value for users without edit-sensitive permission. --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" disabled @checked($employee?->{$fieldName} ?? $field['default'])>
                            <label class="form-check-label">{{ $field['label'] }}</label>
                        </div>
                        <input type="hidden" name="{{ $fieldName }}" value="{{ ($employee?->{$fieldName} ?? $field['default']) ? 1 : 0 }}">
                    @endif
                </div>
            @endforeach
        </div>
        <div class="row g-3">
            <div class="col-12"><x-form.textarea name="applicability_override_reason" label="Applicability Override Reason (required if different from Employee Type default)" :value="$employee?->applicability_override_reason" rows="2" /></div>
        </div>

        @if($isEdit && $employee->hasCompletedRegistration())
            <div class="row g-3 mt-2">
                <div class="col-12"><x-form.textarea name="change_reason" label="Reason for Change (required when changing Department, Section, Designation, Contractor, Date of Joining, or Shift Type)" rows="2" /></div>
            </div>
        @endif
    </div>

    <div class="tab-pane fade" id="tab-contractor">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Contractor <span class="text-danger contractor-required-mark">*</span></label>
                <select name="contractor_id" id="contractor_id" class="form-select">
                    <option value="">Select a Contractor</option>
                    @if($employee?->contractor)
                        <option value="{{ $employee->contractor->id }}" selected>{{ $employee->contractor->legal_name }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contractor Branch Engagement <span class="text-danger contractor-required-mark">*</span></label>
                <input type="hidden" name="contractor_branch_engagement_id" id="contractor_branch_engagement_id" value="{{ $employee?->contractor_branch_engagement_id }}">
                <input type="text" id="contractor_branch_engagement_display" class="form-control" value="{{ $employee?->contractorBranchEngagement?->agreement_number ?? '' }}" disabled placeholder="Resolved automatically from Contractor">
            </div>
            <div class="col-md-4"><label class="form-label">Agreement Number</label><input type="text" class="form-control" id="engagement_agreement_number" value="{{ $employee?->contractorBranchEngagement?->agreement_number }}" disabled></div>
            <div class="col-md-4"><label class="form-label">Contract Start Date</label><input type="text" class="form-control" id="engagement_start_date" value="{{ $employee?->contractorBranchEngagement?->contract_start_date?->format(config('hrms.date_format')) }}" disabled></div>
            <div class="col-md-4"><label class="form-label">Contract End Date</label><input type="text" class="form-control" id="engagement_end_date" value="{{ $employee?->contractorBranchEngagement?->contract_end_date?->format(config('hrms.date_format')) ?? 'Open' }}" disabled></div>
            <div class="col-md-4"><label class="form-label">Labour Licence Number</label><input type="text" class="form-control" id="engagement_licence_number" value="{{ \App\Models\Contractor::maskStatutoryNumber($employee?->contractorBranchEngagement?->effectiveLicenceNumber()) }}" disabled></div>
            <div class="col-md-4"><label class="form-label">Licence Validity</label><input type="text" class="form-control" id="engagement_licence_validity" value="{{ $employee?->contractorBranchEngagement?->effectiveLicenceValidTo()?->format(config('hrms.date_format')) ?? '-' }}" disabled></div>
            <div class="col-md-4"><label class="form-label">Maximum Labour Count</label><input type="text" class="form-control" id="engagement_max_labour" value="{{ $employee?->contractorBranchEngagement?->maximum_labour_count ?? 'Not configured' }}" disabled></div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-shift">
        <div class="row g-3">
            <div class="col-md-4">
                <x-form.select name="shift_type" id="shift_type" label="Shift Type" :options="['fixed' => 'Fixed', 'rotational' => 'Rotational']" :value="$employee?->shift_type" required />
            </div>
            <div class="col-md-4" id="fixed-shift-wrapper">
                <label class="form-label">Fixed Shift</label>
                <select name="fixed_shift_id" id="fixed_shift_id" class="form-select">
                    <option value="">Select a Fixed Shift</option>
                    @if($employee?->fixedShift)
                        <option value="{{ $employee->fixedShift->id }}" selected>{{ $employee->fixedShift->shift_name }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-4" id="rotational-shift-note">
                <div class="alert alert-info mb-0 py-2 px-3 small">Rotational Shifts will be assigned manually after Employee Registration.</div>
            </div>
            <div class="col-12"><x-form.textarea name="shift_type_override_reason" label="Shift Override Reason (required if different from Employee Type default)" :value="$employee?->shift_type_override_reason" rows="2" /></div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-statutory">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><x-form.input name="statutory[aadhaar_number]" label="Aadhaar Number" placeholder="12-digit number" /></div>
            <div class="col-md-4"><x-form.input name="statutory[pan_number]" label="PAN" :value="$statutory?->pan_number" /></div>
            <div class="col-md-4"><x-form.input name="statutory[uan_number]" label="UAN Number" :value="$statutory?->uan_number" /></div>
            <div class="col-md-4"><x-form.input name="statutory[pf_number]" label="PF Number" :value="$statutory?->pf_number" /></div>
            <div class="col-md-4"><x-form.input name="statutory[esi_number]" label="ESI Number" :value="$statutory?->esi_number" /></div>
        </div>
        <div class="row g-3">
            <div class="col-md-3"><x-form.checkbox name="statutory[professional_tax_applicable]" label="Professional Tax Applicable" :checked="$statutory?->professional_tax_applicable ?? true" /></div>
            <div class="col-md-3"><x-form.checkbox name="statutory[pf_applicable]" label="PF Applicable" :checked="$statutory?->pf_applicable ?? true" /></div>
            <div class="col-md-3"><x-form.checkbox name="statutory[esi_applicable]" label="ESI Applicable" :checked="$statutory?->esi_applicable ?? true" /></div>
            <div class="col-md-3"><x-form.checkbox name="statutory[tds_applicable]" label="TDS Applicable" :checked="$statutory?->tds_applicable ?? true" /></div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-bank">
        <div class="row g-3">
            <div class="col-md-4"><x-form.input name="bank[account_holder_name]" label="Account Holder Name" :value="$bank?->account_holder_name" /></div>
            <div class="col-md-4"><x-form.input name="bank[bank_name]" label="Bank Name" :value="$bank?->bank_name" /></div>
            <div class="col-md-4"><x-form.input name="bank[branch_name]" label="Bank Branch" :value="$bank?->branch_name" /></div>
            <div class="col-md-4"><x-form.input name="bank[account_number]" label="Account Number" /></div>
            <div class="col-md-4">
                <x-form.select name="bank[account_type]" label="Account Type" :options="['savings' => 'Savings', 'current' => 'Current', 'other' => 'Other']" :value="$bank?->account_type">
                    <option value="">Select</option>
                </x-form.select>
            </div>
            <div class="col-md-4"><x-form.input name="bank[ifsc_code]" label="IFSC Code" :value="$bank?->ifsc_code" /></div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-emergency">
        <div id="emergency-contacts-list">
            @forelse($emergencyContacts as $index => $contact)
                <div class="row g-3 mb-3 pb-3 border-bottom emergency-contact-row">
                    <div class="col-md-3"><x-form.input name="emergency_contacts[{{ $index }}][name]" label="Name" :value="$contact->name" /></div>
                    <div class="col-md-3"><x-form.input name="emergency_contacts[{{ $index }}][relationship]" label="Relationship" :value="$contact->relationship" /></div>
                    <div class="col-md-2"><x-form.input name="emergency_contacts[{{ $index }}][primary_phone]" label="Primary Phone" :value="$contact->primary_phone" /></div>
                    <div class="col-md-2"><x-form.input name="emergency_contacts[{{ $index }}][alternate_phone]" label="Alternate Phone" :value="$contact->alternate_phone" /></div>
                    <div class="col-md-1 d-flex align-items-end">
                        <x-form.checkbox name="emergency_contacts[{{ $index }}][is_primary]" label="Primary" :checked="$contact->is_primary" />
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-emergency-contact">Remove</button>
                    </div>
                </div>
            @empty
                <div class="row g-3 mb-3 pb-3 border-bottom emergency-contact-row">
                    <div class="col-md-3"><x-form.input name="emergency_contacts[0][name]" label="Name" /></div>
                    <div class="col-md-3"><x-form.input name="emergency_contacts[0][relationship]" label="Relationship" /></div>
                    <div class="col-md-2"><x-form.input name="emergency_contacts[0][primary_phone]" label="Primary Phone" /></div>
                    <div class="col-md-2"><x-form.input name="emergency_contacts[0][alternate_phone]" label="Alternate Phone" /></div>
                    <div class="col-md-1 d-flex align-items-end">
                        <x-form.checkbox name="emergency_contacts[0][is_primary]" label="Primary" :checked="true" />
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-emergency-contact">Remove</button>
                    </div>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-emergency-contact">Add Emergency Contact</button>
    </div>
</div>

<script>
    window.__employeeTypeMeta = @json($employeeTypeMeta);
</script>

@push('scripts')
<script>
(function () {
    var typeMeta = window.__employeeTypeMeta || {};
    var employeeTypeSelect = document.getElementById('employee_type_id');
    var shiftTypeSelect = document.getElementById('shift_type');
    var contractorTabNav = document.getElementById('tab-nav-contractor');

    function applyEmployeeTypeDefaults() {
        var meta = typeMeta[employeeTypeSelect.value];
        contractorTabNav.style.display = (meta && meta.requires_contractor) ? '' : 'none';
        document.querySelectorAll('.contractor-required-mark').forEach(function (el) {
            el.style.display = (meta && meta.requires_contractor) ? '' : 'none';
        });
    }

    function toggleShiftFields() {
        var isFixed = shiftTypeSelect.value === 'fixed';
        document.getElementById('fixed-shift-wrapper').style.display = isFixed ? '' : 'none';
        document.getElementById('rotational-shift-note').style.display = isFixed ? 'none' : '';
    }

    function toggleAddressFields() {
        var checked = document.getElementById('permanent_same_as_current').checked;
        document.getElementById('permanent-address-fields').style.display = checked ? 'none' : '';
    }

    employeeTypeSelect.addEventListener('change', applyEmployeeTypeDefaults);
    shiftTypeSelect.addEventListener('change', toggleShiftFields);
    document.getElementById('permanent_same_as_current').addEventListener('change', toggleAddressFields);

    applyEmployeeTypeDefaults();
    toggleShiftFields();
    toggleAddressFields();

    // Department -> Section -> Designation cascading dropdowns
    var departmentSelect = document.getElementById('department_id');
    var sectionSelect = document.getElementById('section_id');
    var designationSelect = document.getElementById('designation_id');

    function loadDesignations() {
        var params = new URLSearchParams();
        if (departmentSelect.value) params.set('department_id', departmentSelect.value);
        if (sectionSelect.value) params.set('section_id', sectionSelect.value);
        fetch('{{ route('employees.lookup.designations') }}?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var current = designationSelect.value;
                designationSelect.innerHTML = '<option value="">Select a Designation</option>';
                json.data.forEach(function (d) {
                    var opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.designation_code + ' - ' + d.designation_name;
                    if (String(d.id) === String(current)) opt.selected = true;
                    designationSelect.appendChild(opt);
                });
            });
    }

    function loadSections(preserveSelection) {
        if (!departmentSelect.value) {
            sectionSelect.innerHTML = '<option value="">Select a Section</option>';
            loadDesignations();
            return;
        }
        fetch('{{ url('employees/lookup/departments') }}/' + departmentSelect.value + '/sections')
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var current = preserveSelection ? sectionSelect.value : '';
                sectionSelect.innerHTML = '<option value="">Select a Section</option>';
                json.data.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.section_code + ' - ' + s.section_name;
                    if (String(s.id) === String(current)) opt.selected = true;
                    sectionSelect.appendChild(opt);
                });
                loadDesignations();
            });
    }

    departmentSelect.addEventListener('change', function () { loadSections(false); });
    sectionSelect.addEventListener('change', loadDesignations);

    // Contractor -> Engagement details
    var contractorSelect = document.getElementById('contractor_id');

    function loadContractors() {
        var dateOfJoining = document.getElementById('date_of_joining').value;
        fetch('{{ route('employees.lookup.contractors') }}?date_of_joining=' + encodeURIComponent(dateOfJoining || ''))
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var current = contractorSelect.value;
                contractorSelect.innerHTML = '<option value="">Select a Contractor</option>';
                json.data.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.contractor_code + ' - ' + c.legal_name;
                    if (String(c.id) === String(current)) opt.selected = true;
                    contractorSelect.appendChild(opt);
                });
            });
    }

    function loadEngagement() {
        if (!contractorSelect.value) {
            document.getElementById('contractor_branch_engagement_id').value = '';
            return;
        }
        fetch('{{ url('employees/lookup/contractors') }}/' + contractorSelect.value + '/engagement')
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var data = json.data;
                document.getElementById('contractor_branch_engagement_id').value = data ? data.id : '';
                document.getElementById('contractor_branch_engagement_display').value = data ? data.agreement_number || ('Engagement #' + data.id) : '';
                document.getElementById('engagement_agreement_number').value = data ? (data.agreement_number || '-') : '';
                document.getElementById('engagement_start_date').value = data ? (data.contract_start_date || '-') : '';
                document.getElementById('engagement_end_date').value = data ? (data.contract_end_date || 'Open') : '';
                document.getElementById('engagement_licence_number').value = data ? (data.labour_licence_number || '-') : '';
                document.getElementById('engagement_licence_validity').value = data ? (data.licence_valid_to || '-') : '';
                document.getElementById('engagement_max_labour').value = data ? (data.maximum_labour_count || 'Not configured') : '';
            });
    }

    contractorSelect.addEventListener('change', loadEngagement);
    document.getElementById('date_of_joining').addEventListener('change', loadContractors);

    // Fixed shift options depend on Employee Type + Date of Joining
    var fixedShiftSelect = document.getElementById('fixed_shift_id');

    function loadFixedShifts() {
        var params = new URLSearchParams();
        if (employeeTypeSelect.value) params.set('employee_type_id', employeeTypeSelect.value);
        var doj = document.getElementById('date_of_joining').value;
        if (doj) params.set('date_of_joining', doj);
        fetch('{{ route('employees.lookup.fixed-shifts') }}?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var current = fixedShiftSelect.value;
                fixedShiftSelect.innerHTML = '<option value="">Select a Fixed Shift</option>';
                json.data.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.shift_code + ' - ' + s.shift_name + ' (' + s.start_time + ' - ' + s.end_time + ')';
                    if (String(s.id) === String(current)) opt.selected = true;
                    fixedShiftSelect.appendChild(opt);
                });
            });
    }

    employeeTypeSelect.addEventListener('change', loadFixedShifts);
    document.getElementById('date_of_joining').addEventListener('change', loadFixedShifts);

    // Emergency contact add/remove
    var emergencyList = document.getElementById('emergency-contacts-list');
    var emergencyIndex = emergencyList.querySelectorAll('.emergency-contact-row').length;

    document.getElementById('add-emergency-contact').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'row g-3 mb-3 pb-3 border-bottom emergency-contact-row';
        row.innerHTML = '<div class="col-md-3"><div class="mb-3"><label class="form-label">Name</label><input type="text" name="emergency_contacts[' + emergencyIndex + '][name]" class="form-control"></div></div>' +
            '<div class="col-md-3"><div class="mb-3"><label class="form-label">Relationship</label><input type="text" name="emergency_contacts[' + emergencyIndex + '][relationship]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="mb-3"><label class="form-label">Primary Phone</label><input type="text" name="emergency_contacts[' + emergencyIndex + '][primary_phone]" class="form-control"></div></div>' +
            '<div class="col-md-2"><div class="mb-3"><label class="form-label">Alternate Phone</label><input type="text" name="emergency_contacts[' + emergencyIndex + '][alternate_phone]" class="form-control"></div></div>' +
            '<div class="col-md-1 d-flex align-items-end"><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="emergency_contacts[' + emergencyIndex + '][is_primary]" value="1"><label class="form-check-label">Primary</label></div></div>' +
            '<div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger remove-emergency-contact mb-3">Remove</button></div>';
        emergencyList.appendChild(row);
        emergencyIndex++;
    });

    emergencyList.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-emergency-contact')) {
            event.target.closest('.emergency-contact-row').remove();
        }
    });
})();
</script>
@endpush
