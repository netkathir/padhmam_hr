@extends('layouts.admin')

@section('content')
    <x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Designation Master', 'url' => route('masters.designations.index')], ['label' => $designation->designation_name]]" />

    <x-page-header title="Edit Designation" :subtitle="$designation->designation_code.' — '.$designation->designation_name">
        <x-cancel-button href="{{ route('masters.designations.show', $designation) }}">Back</x-cancel-button>
    </x-page-header>

    <x-branch-context-badge />

    @php
        $currentScope = $designation->scopeLevel();
    @endphp

    <div class="page-surface p-4">
        <form method="post" action="{{ route('masters.designations.update', $designation) }}" id="designation-form">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4"><x-form.input name="designation_code" label="Designation Code" :value="$designation->designation_code" required /></div>
                <div class="col-md-8"><x-form.input name="designation_name" label="Designation Name" :value="$designation->designation_name" required /></div>

                <div class="col-md-4">
                    <x-form.select name="scope" label="Scope" :options="\App\Models\Designation::SCOPE_LABELS" :value="$currentScope" required />
                </div>
                <div class="col-md-4" id="department-field">
                    <x-form.select name="department_id" label="Department" :options="$departments->pluck('department_name', 'id')" :value="$designation->department_id">
                        <option value="">Select a Department</option>
                    </x-form.select>
                </div>
                <div class="col-md-4" id="section-field">
                    <x-form.select name="section_id" label="Section" :options="$sections->pluck('section_name', 'id')" :value="$designation->section_id">
                        <option value="">Select a Section</option>
                    </x-form.select>
                </div>

                <div class="col-md-4"><x-form.input name="short_name" label="Short Name" :value="$designation->short_name" /></div>
                <div class="col-md-4"><x-form.input type="number" name="hierarchy_level" label="Hierarchy Level" :value="$designation->hierarchy_level" /></div>
                <div class="col-md-4"><x-form.input type="number" name="display_order" label="Display Order" :value="$designation->display_order" /></div>
                <div class="col-md-4">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$designation->status" required />
                </div>
                <div class="col-12"><x-form.textarea name="description" label="Description" :value="$designation->description" /></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-submit-button label="Save Changes" />
                <x-cancel-button href="{{ route('masters.designations.show', $designation) }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const scopeSelect = document.getElementById('scope');
    const departmentField = document.getElementById('department-field');
    const sectionField = document.getElementById('section-field');
    const departmentSelect = document.getElementById('department_id');
    const sectionSelect = document.getElementById('section_id');
    let initialLoad = true;

    function applyScope() {
        const scope = scopeSelect.value;

        departmentField.classList.toggle('d-none', scope === 'branch');
        sectionField.classList.toggle('d-none', scope !== 'section');

        if (scope === 'branch') {
            departmentSelect.value = '';
            sectionSelect.innerHTML = '<option value="">Select a Section</option>';
        }

        if (scope !== 'section') {
            sectionSelect.value = '';
        }
    }

    function loadSections() {
        const departmentId = departmentSelect.value;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;

        if (!departmentId) {
            sectionSelect.innerHTML = '<option value="">Select a Department first</option>';
            sectionSelect.disabled = false;
            return;
        }

        fetch('{{ url('masters/departments') }}/' + departmentId + '/sections', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((payload) => {
                const sections = payload.data || [];
                sectionSelect.innerHTML = '<option value="">Select a Section</option>';

                if (sections.length === 0) {
                    sectionSelect.innerHTML = '<option value="">No active sections in this department</option>';
                } else {
                    sections.forEach((section) => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.section_name;
                        sectionSelect.appendChild(option);
                    });
                }

                sectionSelect.disabled = false;
            })
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">Unable to load sections</option>';
                sectionSelect.disabled = false;
            });
    }

    scopeSelect.addEventListener('change', applyScope);
    departmentSelect.addEventListener('change', function () {
        if (scopeSelect.value === 'section') {
            loadSections();
        }
    });

    applyScope();
})();
</script>
@endpush
