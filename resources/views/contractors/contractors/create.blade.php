@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Contractor Master', 'url' => route('contractors.master.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Contractor" subtitle="Register a new contractor for the organization.">
        <x-cancel-button href="{{ route('contractors.master.index') }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('contractors.master.store') }}">
            @csrf

            <h2 class="h6 text-uppercase text-muted mb-3">Basic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="contractor_code" label="Contractor Code" placeholder="e.g. CONT-001" required /></div>
                <div class="col-md-8"><x-form.input name="legal_name" label="Legal Name" required /></div>
                <div class="col-md-6"><x-form.input name="trade_name" label="Trade Name" /></div>
                <div class="col-md-6">
                    <x-form.select name="contractor_type" label="Contractor Type" :options="\App\Models\Contractor::TYPES" />
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Contact Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="contact_person_name" label="Contact Person Name" required /></div>
                <div class="col-md-4"><x-form.input name="primary_phone" label="Primary Phone" required /></div>
                <div class="col-md-4"><x-form.input name="alternate_phone" label="Alternate Phone" /></div>
                <div class="col-md-6"><x-form.input type="email" name="primary_email" label="Primary Email" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Registered Address</h2>
            <div class="row g-3 mb-4">
                <div class="col-12"><x-form.input name="address_line_1" label="Address Line 1" required /></div>
                <div class="col-12"><x-form.input name="address_line_2" label="Address Line 2" /></div>
                <div class="col-md-3"><x-form.input name="city" label="City" required /></div>
                <div class="col-md-3"><x-form.input name="district" label="District" /></div>
                <div class="col-md-3"><x-form.input name="state" label="State" required /></div>
                <div class="col-md-3"><x-form.input name="postal_code" label="Postal Code" required /></div>
                <div class="col-md-3"><x-form.input name="country" label="Country" value="India" required /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Statutory Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input name="pan_number" label="PAN" placeholder="AAAAA9999A" /></div>
                <div class="col-md-3"><x-form.input name="gstin" label="GSTIN" /></div>
                <div class="col-md-3"><x-form.input name="pf_registration_number" label="PF Registration Number" /></div>
                <div class="col-md-3"><x-form.input name="esi_registration_number" label="ESI Registration Number" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Labour Licence</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="labour_licence_number" label="Labour Licence Number" /></div>
                <div class="col-md-4"><x-form.input type="date" name="labour_licence_valid_from" label="Licence Valid From" /></div>
                <div class="col-md-4"><x-form.input type="date" name="labour_licence_valid_to" label="Licence Valid To" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Status and Notes</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                </div>
                <div class="col-md-9"><x-form.textarea name="description" label="Description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Contractor" />
                <x-cancel-button href="{{ route('contractors.master.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
