@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2">HRMS Foundation</span>
        </div>
        <h1 class="h4 mb-2">{{ config('hrms.app_name') }}</h1>
        <p class="text-muted mb-0">Sign in to continue to the administration dashboard.</p>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('login.store') }}" class="needs-validation" novalidate>
        @csrf
        <x-form.input name="login" label="Username or Email" required placeholder="Enter your username or email" />
        <x-form.input type="password" name="password" label="Password" required placeholder="Enter your password" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-form.checkbox name="remember" label="Remember me" :checked="old('remember')" />
            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>
@endsection
