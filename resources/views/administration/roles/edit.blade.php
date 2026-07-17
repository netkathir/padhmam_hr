@extends('layouts.admin')

@section('content')
    <x-page-header title="Manage Role Permissions" subtitle="Assign foundation permissions to the role.">
        <x-cancel-button href="{{ route('roles.index') }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                @foreach($permissions as $group => $groupPermissions)
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="fw-semibold mb-3">{{ $group }}</div>
                            @foreach($groupPermissions as $permission)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id))>
                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }} <span class="text-muted small">({{ $permission->slug }})</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">Save Permissions</button>
                <x-cancel-button href="{{ route('roles.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
