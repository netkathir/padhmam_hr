@extends('layouts.guest')

@section('content')
    <h1 class="h4 mb-2">Reset Password</h1>
    <p class="text-muted mb-4">Choose a new password for your account.</p>

    <form method="post" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input type="email" name="email" label="Email Address" required />
        <x-form.input type="password" name="password" label="New Password" required />
        <x-form.input type="password" name="password_confirmation" label="Confirm Password" required />
        <div class="d-flex justify-content-between align-items-center">
            <x-cancel-button href="{{ route('login') }}">Back to login</x-cancel-button>
            <button class="btn btn-primary" type="submit">Reset Password</button>
        </div>
    </form>
@endsection
