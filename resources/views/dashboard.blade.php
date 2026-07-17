@extends('layouts.admin')

@section('content')
    <x-page-header title="Dashboard" subtitle="Foundation overview for the HRMS application." />

    <div class="row g-3">
        <div class="col-md-3">
            <div class="page-surface p-3 h-100">
                <div class="text-muted small">Welcome</div>
                <div class="fs-5 fw-semibold">{{ $user?->name }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="page-surface p-3 h-100">
                <div class="text-muted small">Current Role</div>
                <div class="fs-5 fw-semibold">{{ $user?->primaryRoleName() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="page-surface p-3 h-100">
                <div class="text-muted small">Active Branch</div>
                <div class="fs-5 fw-semibold">{{ $activeBranch ?? config('hrms.branch_all_label') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="page-surface p-3 h-100">
                <div class="text-muted small">Current Date</div>
                <div class="fs-5 fw-semibold">{{ $currentDate }}</div>
            </div>
        </div>
    </div>

    <div class="page-surface p-4 mt-4">
        <h2 class="h5">Application Status</h2>
        <p class="text-muted mb-0">
            Foundation modules are active. Business modules such as employees, attendance, leave, payroll, and reports will be added later.
        </p>
    </div>
@endsection
