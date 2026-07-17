@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Organization Profile', 'url' => route('organization.show')], ['label' => 'Edit']]" />

    <x-page-header title="Edit Organization Profile" subtitle="Update the company-level profile.">
        <x-cancel-button href="{{ route('organization.show') }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('organization.update') }}">
            @csrf
            @method('PUT')

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basic Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address" type="button">Registered Address</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button">Contact Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button">Settings</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-basic">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="organization_code" label="Organization Code" :value="$organization->organization_code" required /></div>
                        <div class="col-md-8"><x-form.input name="legal_name" label="Legal Name" :value="$organization->legal_name" required /></div>
                        <div class="col-md-6"><x-form.input name="display_name" label="Display Name" :value="$organization->display_name" required /></div>
                        <div class="col-md-6"><x-form.input name="business_type" label="Business Type" :value="$organization->business_type" placeholder="e.g. Manufacturing" /></div>
                        <div class="col-md-6"><x-form.input type="date" name="incorporation_date" label="Incorporation Date" :value="$organization->incorporation_date?->format('Y-m-d')" /></div>
                        <div class="col-md-6">
                            <x-form.select name="financial_year_start_month" label="Financial Year Start Month" required :value="(string) $organization->financial_year_start_month" :options="[
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                            ]" />
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-address">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="address_line_1" label="Address Line 1" :value="$organization->address_line_1" required /></div>
                        <div class="col-md-6"><x-form.input name="address_line_2" label="Address Line 2" :value="$organization->address_line_2" /></div>
                        <div class="col-md-3"><x-form.input name="city" label="City" :value="$organization->city" required /></div>
                        <div class="col-md-3"><x-form.input name="district" label="District" :value="$organization->district" /></div>
                        <div class="col-md-3"><x-form.input name="state" label="State" :value="$organization->state" required /></div>
                        <div class="col-md-3"><x-form.input name="country" label="Country" :value="$organization->country" required /></div>
                        <div class="col-md-3"><x-form.input name="postal_code" label="Postal Code" :value="$organization->postal_code" required /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-contact">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="primary_phone" label="Primary Phone" :value="$organization->primary_phone" /></div>
                        <div class="col-md-6"><x-form.input name="alternate_phone" label="Alternate Phone" :value="$organization->alternate_phone" /></div>
                        <div class="col-md-6"><x-form.input type="email" name="primary_email" label="Primary Email" :value="$organization->primary_email" /></div>
                        <div class="col-md-6"><x-form.input name="website" label="Website" :value="$organization->website" placeholder="https://" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-statutory">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="pan_number" label="PAN" :value="$organization->pan_number" placeholder="AAAAA9999A" /></div>
                        <div class="col-md-4"><x-form.input name="tan_number" label="TAN" :value="$organization->tan_number" placeholder="AAAA99999A" /></div>
                        <div class="col-md-4"><x-form.input name="gstin" label="GSTIN" :value="$organization->gstin" /></div>
                        <div class="col-md-4"><x-form.input name="pf_registration_number" label="PF Registration Number" :value="$organization->pf_registration_number" /></div>
                        <div class="col-md-4"><x-form.input name="esi_registration_number" label="ESI Registration Number" :value="$organization->esi_registration_number" /></div>
                        <div class="col-md-4"><x-form.input name="professional_tax_number" label="Professional Tax Number" :value="$organization->professional_tax_number" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-settings">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$organization->status" required />
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                The organization logo is managed from the <a href="{{ route('organization.show') }}">profile page</a>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('organization.show') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
