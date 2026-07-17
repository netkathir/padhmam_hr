@extends('layouts.admin')

@section('content')
    <x-page-header title="Audit Log Details" subtitle="Inspect the recorded administrative event." />

    <div class="page-surface p-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Event</dt>
            <dd class="col-sm-9">{{ $auditLog->event }}</dd>
            <dt class="col-sm-3">Module</dt>
            <dd class="col-sm-9">{{ $auditLog->module }}</dd>
            <dt class="col-sm-3">User</dt>
            <dd class="col-sm-9">{{ $auditLog->user?->name ?? 'System' }}</dd>
            <dt class="col-sm-3">Branch</dt>
            <dd class="col-sm-9">{{ $auditLog->branch?->name ?? 'Company Level' }}</dd>
            <dt class="col-sm-3">New Values</dt>
            <dd class="col-sm-9"><pre class="mb-0">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></dd>
        </dl>
    </div>
@endsection
