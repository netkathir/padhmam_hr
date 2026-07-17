@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Branches', 'url' => route('branches.index')], ['label' => $branch->branch_name]]" />

    <x-page-header title="Edit Branch" :subtitle="$branch->branch_code.' — '.$branch->branch_name">
        <x-cancel-button href="{{ route('branches.show', $branch) }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basic Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address" type="button">Address</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contact" type="button">Contact Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statutory" type="button">Statutory Information</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button">Display Settings</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-basic">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="branch_code" label="Branch Code" :value="$branch->branch_code" required /></div>
                        <div class="col-md-8"><x-form.input name="branch_name" label="Branch Name" :value="$branch->branch_name" required /></div>
                        <div class="col-md-6"><x-form.input name="short_name" label="Short Name" :value="$branch->short_name" /></div>
                        <div class="col-md-6">
                            <x-form.select name="branch_type" label="Branch Type" :options="$branchTypes" :value="$branch->branch_type" required />
                        </div>
                    </div>
                    @if($branch->isHeadOffice())
                        <div class="alert alert-primary">This branch is the current Head Office.</div>
                    @endif
                </div>

                <div class="tab-pane fade" id="tab-address">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="address_line_1" label="Address Line 1" :value="$branch->address_line_1" required /></div>
                        <div class="col-md-6"><x-form.input name="address_line_2" label="Address Line 2" :value="$branch->address_line_2" /></div>
                        <div class="col-md-3"><x-form.input name="city" label="City" :value="$branch->city" required /></div>
                        <div class="col-md-3"><x-form.input name="district" label="District" :value="$branch->district" /></div>
                        <div class="col-md-3"><x-form.input name="state" label="State" :value="$branch->state" required /></div>
                        <div class="col-md-3"><x-form.input name="country" label="Country" :value="$branch->country" required /></div>
                        <div class="col-md-3"><x-form.input name="postal_code" label="Postal Code" :value="$branch->postal_code" required /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-contact">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="phone" label="Phone" :value="$branch->phone" /></div>
                        <div class="col-md-6"><x-form.input name="alternate_phone" label="Alternate Phone" :value="$branch->alternate_phone" /></div>
                        <div class="col-md-6"><x-form.input type="email" name="email" label="Email" :value="$branch->email" /></div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6"><x-form.input name="contact_person_name" label="Contact Person Name" :value="$branch->contact_person_name" /></div>
                        <div class="col-md-6"><x-form.input name="contact_person_phone" label="Contact Person Phone" :value="$branch->contact_person_phone" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-statutory">
                    <div class="row g-3">
                        <div class="col-md-6"><x-form.input name="gstin" label="Branch GSTIN" :value="$branch->gstin" /></div>
                        <div class="col-md-6"><x-form.input name="establishment_code" label="Establishment Code" :value="$branch->establishment_code" /></div>
                        <div class="col-md-4"><x-form.input name="pf_sub_code" label="PF Sub-code" :value="$branch->pf_sub_code" /></div>
                        <div class="col-md-4"><x-form.input name="esi_sub_code" label="ESI Sub-code" :value="$branch->esi_sub_code" /></div>
                        <div class="col-md-4"><x-form.input name="professional_tax_number" label="Professional Tax Number" :value="$branch->professional_tax_number" /></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-settings">
                    <div class="row g-3">
                        <div class="col-md-4"><x-form.input name="timezone" label="Timezone" :value="$branch->timezone" required /></div>
                        <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" :value="$branch->display_order" /></div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                Status and Head Office assignment are managed from the branch detail page, not this form.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('branches.show', $branch) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
