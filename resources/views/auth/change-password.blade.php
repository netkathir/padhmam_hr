@extends('layouts.admin')

@section('content')
    <x-page-header title="Change Password" subtitle="Update your login password." />

    <div class="page-surface p-4">
        <form method="post" action="{{ route('password.change.update') }}">
            @csrf
            @method('PUT')
            <x-form.input type="password" name="current_password" label="Current Password" required />
            <x-form.input type="password" name="password" label="New Password" required />
            <x-form.input type="password" name="password_confirmation" label="Confirm New Password" required />
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Update Password</button>
                <x-cancel-button href="{{ route('dashboard') }}">Cancel</x-cancel-button>
            </div>
        </form>
    </div>
@endsection
