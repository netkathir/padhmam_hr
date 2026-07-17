@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Contractor Master', 'url' => route('contractors.master.index')], ['label' => $contractor->legal_name]]" />

    <x-page-header title="Edit Contractor" :subtitle="$contractor->contractor_code.' — '.$contractor->legal_name">
        <x-cancel-button href="{{ route('contractors.master.show', $contractor) }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('contractors.master.update', $contractor) }}">
            @csrf
            @method('PUT')

            <h2 class="h6 text-uppercase text-muted mb-3">Basic Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="contractor_code" label="Contractor Code" :value="$contractor->contractor_code" required /></div>
                <div class="col-md-8"><x-form.input name="legal_name" label="Legal Name" :value="$contractor->legal_name" required /></div>
                <div class="col-md-6"><x-form.input name="trade_name" label="Trade Name" :value="$contractor->trade_name" /></div>
                <div class="col-md-6">
                    <x-form.select name="contractor_type" label="Contractor Type" :options="\App\Models\Contractor::TYPES" :value="$contractor->contractor_type" />
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Contact Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="contact_person_name" label="Contact Person Name" :value="$contractor->contact_person_name" required /></div>
                <div class="col-md-4"><x-form.input name="primary_phone" label="Primary Phone" :value="$contractor->primary_phone" required /></div>
                <div class="col-md-4"><x-form.input name="alternate_phone" label="Alternate Phone" :value="$contractor->alternate_phone" /></div>
                <div class="col-md-6"><x-form.input type="email" name="primary_email" label="Primary Email" :value="$contractor->primary_email" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Registered Address</h2>
            <div class="row g-3 mb-4">
                <div class="col-12"><x-form.input name="address_line_1" label="Address Line 1" :value="$contractor->address_line_1" required /></div>
                <div class="col-12"><x-form.input name="address_line_2" label="Address Line 2" :value="$contractor->address_line_2" /></div>
                <div class="col-md-3"><x-form.input name="city" label="City" :value="$contractor->city" required /></div>
                <div class="col-md-3"><x-form.input name="district" label="District" :value="$contractor->district" /></div>
                <div class="col-md-3"><x-form.input name="state" label="State" :value="$contractor->state" required /></div>
                <div class="col-md-3"><x-form.input name="postal_code" label="Postal Code" :value="$contractor->postal_code" required /></div>
                <div class="col-md-3"><x-form.input name="country" label="Country" :value="$contractor->country" required /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Statutory Information</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><x-form.input name="pan_number" label="PAN" :value="$contractor->pan_number" /></div>
                <div class="col-md-3"><x-form.input name="gstin" label="GSTIN" :value="$contractor->gstin" /></div>
                <div class="col-md-3"><x-form.input name="pf_registration_number" label="PF Registration Number" :value="$contractor->pf_registration_number" /></div>
                <div class="col-md-3"><x-form.input name="esi_registration_number" label="ESI Registration Number" :value="$contractor->esi_registration_number" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Labour Licence</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><x-form.input name="labour_licence_number" label="Labour Licence Number" :value="$contractor->labour_licence_number" /></div>
                <div class="col-md-4"><x-form.input type="date" name="labour_licence_valid_from" label="Licence Valid From" :value="$contractor->labour_licence_valid_from?->format('Y-m-d')" /></div>
                <div class="col-md-4"><x-form.input type="date" name="labour_licence_valid_to" label="Licence Valid To" :value="$contractor->labour_licence_valid_to?->format('Y-m-d')" /></div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3">Status and Notes</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$contractor->status" required />
                </div>
                <div class="col-md-9"><x-form.textarea name="description" label="Description" :value="$contractor->description" /></div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('contractors.master.show', $contractor) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
