@extends('layouts.guest')

@section('content')
    <h1 class="h4 mb-2">Forgot Password</h1>
    <p class="text-muted mb-4">Enter your email address and we will send a reset link.</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <x-form.input type="email" name="email" label="Email Address" required />
        <div class="d-flex justify-content-between align-items-center">
            <x-cancel-button href="{{ route('login') }}">Back to login</x-cancel-button>
            <button class="btn btn-primary" type="submit">Send Reset Link</button>
        </div>
    </form>
@endsection
