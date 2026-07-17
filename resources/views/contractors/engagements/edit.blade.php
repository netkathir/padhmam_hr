@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branch Engagements', 'url' => route('contractors.engagements.index')], ['label' => $engagement->contractor->legal_name]]" />

    <x-page-header title="Edit Branch Engagement" :subtitle="$engagement->contractor->legal_name.' — '.$engagement->branch->branch_name">
        <x-cancel-button href="{{ route('contractors.engagements.show', $engagement) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('contractors.engagements.update', $engagement) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contractor</label>
                    <input type="text" class="form-control" value="{{ $engagement->contractor->legal_name }}" disabled>
                </div>
                <div class="col-md-6"><x-form.input name="agreement_number" label="Agreement Number" :value="$engagement->agreement_number" /></div>
                <div class="col-md-4"><x-form.input type="date" name="agreement_date" label="Agreement Date" :value="$engagement->agreement_date?->format('Y-m-d')" /></div>
                <div class="col-md-4"><x-form.input type="date" name="contract_start_date" label="Contract Start Date" :value="$engagement->contract_start_date?->format('Y-m-d')" required /></div>
                <div class="col-md-4"><x-form.input type="date" name="contract_end_date" label="Contract End Date" :value="$engagement->contract_end_date?->format('Y-m-d')" /></div>
                <div class="col-md-4"><x-form.input type="number" name="maximum_labour_count" label="Maximum Labour Count" :value="$engagement->maximum_labour_count" placeholder="Leave blank for no limit" /></div>
                <div class="col-md-4"><x-form.input name="branch_labour_licence_number" label="Branch Labour Licence Number" :value="$engagement->branch_labour_licence_number" /></div>
                <div class="col-md-4"></div>
                <div class="col-md-4"><x-form.input type="date" name="branch_licence_valid_from" label="Branch Licence Valid From" :value="$engagement->branch_licence_valid_from?->format('Y-m-d')" /></div>
                <div class="col-md-4"><x-form.input type="date" name="branch_licence_valid_to" label="Branch Licence Valid To" :value="$engagement->branch_licence_valid_to?->format('Y-m-d')" /></div>
                <div class="col-md-6"><x-form.input name="contact_person_name" label="Branch Contact Person" :value="$engagement->contact_person_name" /></div>
                <div class="col-md-6"><x-form.input name="contact_person_phone" label="Branch Contact Phone" :value="$engagement->contact_person_phone" /></div>
                <div class="col-md-3">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$engagement->status" required />
                </div>
                <div class="col-12"><x-form.textarea name="remarks" label="Remarks" :value="$engagement->remarks" /></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('contractors.engagements.show', $engagement) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
