@extends('layouts.admin')

@section('content')
    <x-page-header title="Edit User" subtitle="Update user details, roles, and status.">
        <x-cancel-button href="{{ route('users.index') }}">Back</x-cancel-button>
    </x-page-header>

    <div class="page-surface p-4">
        <form method="post" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><x-form.input name="name" label="Name" :value="$user->name" required /></div>
                <div class="col-md-6"><x-form.input name="username" label="Username" :value="$user->username" required /></div>
                <div class="col-md-6"><x-form.input type="email" name="email" label="Email" :value="$user->email" required /></div>
                <div class="col-md-6"><x-form.input name="phone" label="Phone" :value="$user->phone" /></div>
                <div class="col-md-6"><x-form.input type="password" name="password" label="New Password" /></div>
                <div class="col-md-6"><x-form.input type="password" name="password_confirmation" label="Confirm Password" /></div>
                <div class="col-md-4">
                    <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$user->status" required />
                </div>
            </div>

            <div class="mb-3">
                <div class="fw-semibold mb-2">Roles</div>
                @foreach($roles as $role)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" @checked($user->roles->contains('id', $role->id))>
                        <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Update User</button>
                <x-cancel-button href="{{ route('users.index') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
