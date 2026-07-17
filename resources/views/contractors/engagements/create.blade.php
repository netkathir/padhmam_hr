@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branch Engagements', 'url' => route('contractors.engagements.index')], ['label' => 'Create']]" />

    <x-page-header title="Create Branch Engagement" subtitle="Engage a contractor for the active branch.">
        <x-cancel-button href="{{ route('contractors.engagements.index') }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('contractors.engagements.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-form.select name="contractor_id" label="Contractor" :options="$contractors->pluck('legal_name', 'id')" required>
                        <option value="">Select a Contractor</option>
                    </x-form.select>
                </div>
                <div class="col-md-6"><x-form.input name="agreement_number" label="Agreement Number" /></div>
                <div class="col-md-4"><x-form.input type="date" name="agreement_date" label="Agreement Date" /></div>
                <div class="col-md-4"><x-form.input type="date" name="contract_start_date" label="Contract Start Date" required /></div>
                <div class="col-md-4"><x-form.input type="date" name="contract_end_date" label="Contract End Date" /></div>
                <div class="col-md-4"><x-form.input type="number" name="maximum_labour_count" label="Maximum Labour Count" placeholder="Leave blank for no limit" /></div>
                <div class="col-md-4"><x-form.input name="branch_labour_licence_number" label="Branch Labour Licence Number" /></div>
                <div class="col-md-4"></div>
                <div class="col-md-4"><x-form.input type="date" name="branch_licence_valid_from" label="Branch Licence Valid From" /></div>
                <div class="col-md-4"><x-form.input type="date" name="branch_licence_valid_to" label="Branch Licence Valid To" /></div>
                <div class="col-md-6"><x-form.input name="contact_person_name" label="Branch Contact Person" /></div>
                <div class="col-md-6"><x-form.input name="contact_person_phone" label="Branch Contact Phone" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                </div>
                <div class="col-12"><x-form.textarea name="remarks" label="Remarks" /></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Engagement" />
                <x-cancel-button href="{{ route('contractors.engagements.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
