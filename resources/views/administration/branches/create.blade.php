@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branches', 'url' => route('branches.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Branch" subtitle="Add a new branch to the organization.">
        <x-cancel-button href="{{ route('branches.index') }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('branches.store') }}">
            @csrf

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basic Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address" type="button">Address</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button">Contact Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button">Status and Display</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-basic">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="branch_code" label="Branch Code" placeholder="e.g. UNIT-03" required /></div>
                        <div class="col-md-8"><x-form.input name="branch_name" label="Branch Name" required /></div>
                        <div class="col-md-6"><x-form.input name="short_name" label="Short Name" /></div>
                        <div class="col-md-6">
                            <x-form.select name="branch_type" label="Branch Type" :options="$branchTypes" required />
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-address">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="address_line_1" label="Address Line 1" required /></div>
                        <div class="col-md-6"><x-form.input name="address_line_2" label="Address Line 2" /></div>
                        <div class="col-md-3"><x-form.input name="city" label="City" required /></div>
                        <div class="col-md-3"><x-form.input name="district" label="District" /></div>
                        <div class="col-md-3"><x-form.input name="state" label="State" required /></div>
                        <div class="col-md-3"><x-form.input name="country" label="Country" value="India" required /></div>
                        <div class="col-md-3"><x-form.input name="postal_code" label="Postal Code" required /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-contact">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="phone" label="Phone" /></div>
                        <div class="col-md-6"><x-form.input name="alternate_phone" label="Alternate Phone" /></div>
                        <div class="col-md-6"><x-form.input type="email" name="email" label="Email" /></div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6"><x-form.input name="contact_person_name" label="Contact Person Name" /></div>
                        <div class="col-md-6"><x-form.input name="contact_person_phone" label="Contact Person Phone" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-statutory">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="gstin" label="Branch GSTIN" /></div>
                        <div class="col-md-6"><x-form.input name="establishment_code" label="Establishment Code" /></div>
                        <div class="col-md-4"><x-form.input name="pf_sub_code" label="PF Sub-code" /></div>
                        <div class="col-md-4"><x-form.input name="esi_sub_code" label="ESI Sub-code" /></div>
                        <div class="col-md-4"><x-form.input name="professional_tax_number" label="Professional Tax Number" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-settings">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="timezone" label="Timezone" value="Asia/Kolkata" required /></div>
                        <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" value="0" /></div>
                        <div class="col-md-4">
                            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                New branches are not automatically made Head Office. Use the "Make Head Office" action after creation if required.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Branch" />
                <x-cancel-button href="{{ route('branches.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
