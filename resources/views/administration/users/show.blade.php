@extends('layouts.admin')

@section('content')
    <x-page-header title="User Details" subtitle="Read-only user summary." />

    <div class="page-surface p-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9">{{ $user->username }}</dd>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9">{{ $user->email }}</dd>
            <dt class="col-sm-3">Branch</dt>
            <dd class="col-sm-9">{{ $user->branch?->name ?? 'Super Administrator' }}</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><x-status-badge :status="$user->status" /></dd>
        </dl>
    </div>
@endsection
