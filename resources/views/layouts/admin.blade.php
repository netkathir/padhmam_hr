<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('hrms.app_name') }} | {{ $title ?? 'Dashboard' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-bg: #102a43;
            --sidebar-accent: #1f6feb;
            --surface: #f5f7fb;
        }

        body {
            background: var(--surface);
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .app-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--sidebar-bg), #0b1f33);
            color: #fff;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .app-sidebar a {
            color: rgba(255,255,255,.8);
            text-decoration: none;
        }

        .app-sidebar a.active,
        .app-sidebar a:hover {
            color: #fff;
            background: rgba(255,255,255,.08);
        }

        .sidebar-nav .nav-link {
            border-radius: .75rem;
            margin-bottom: .35rem;
            padding: .7rem .9rem;
        }

        .app-content {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(15,23,42,.08);
        }

        .page-surface {
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15,23,42,.08);
        }

        @media (max-width: 991.98px) {
            .app-sidebar {
                position: fixed;
                left: -100%;
                z-index: 1045;
                transition: left .25s ease;
            }

            body.sidebar-open .app-sidebar {
                left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $branchContext = app(\App\Services\BranchContext::class);
    $currentUser = auth()->user();
    $allBranches = \App\Models\Branch::query()->active()->ordered()->get();
@endphp
<div class="app-shell">
    <aside class="app-sidebar p-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="fw-semibold">{{ config('hrms.app_name') }}</div>
                <small class="text-white-50">Application Foundation</small>
            </div>
            <button class="btn btn-sm btn-outline-light d-lg-none" type="button" data-sidebar-toggle>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="mb-3 p-3 rounded-4" style="background: rgba(255,255,255,.08)">
            <div class="small text-white-50">Current Branch</div>
            <div class="fw-semibold">
                {{ $branchContext->isAllBranchesSelected() ? config('hrms.branch_all_label') : ($branchContext->currentBranch()?->branch_name ?? 'Not selected') }}
            </div>
            <div class="small text-white-50">{{ $currentUser?->primaryRoleName() }}</div>
        </div>

        <nav class="nav flex-column sidebar-nav">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            @can('viewAny', \App\Models\Organization::class)
                <a class="nav-link {{ request()->routeIs('organization.*') ? 'active' : '' }}" href="{{ route('organization.show') }}">
                    <i class="bi bi-building me-2"></i>Organization Profile
                </a>
            @endcan
            @can('viewAny', \App\Models\Branch::class)
                <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                    <i class="bi bi-diagram-3 me-2"></i>Branches
                </a>
            @endcan
            @can('viewAny', \App\Models\User::class)
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            @endcan
            @can('viewAny', \App\Models\Role::class)
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-lock me-2"></i>Roles
                </a>
            @endcan
            @can('viewAny', \App\Models\AuditLog::class)
                <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">
                    <i class="bi bi-journal-text me-2"></i>Audit Logs
                </a>
            @endcan

            @if(auth()->user()?->hasPermissionTo('department.view') || auth()->user()?->hasPermissionTo('section.view') || auth()->user()?->hasPermissionTo('designation.view') || auth()->user()?->hasPermissionTo('employee-type.view'))
                <div class="mt-3 pt-3 border-top border-white border-opacity-25 text-white-50 small">Masters and Configuration</div>
                @can('viewAny', \App\Models\Department::class)
                    <a class="nav-link {{ request()->routeIs('masters.departments.*') ? 'active' : '' }}" href="{{ route('masters.departments.index') }}">
                        <i class="bi bi-diagram-2 me-2"></i>Department Master
                    </a>
                @endcan
                @can('viewAny', \App\Models\Section::class)
                    <a class="nav-link {{ request()->routeIs('masters.sections.*') ? 'active' : '' }}" href="{{ route('masters.sections.index') }}">
                        <i class="bi bi-diagram-3 me-2"></i>Section Master
                    </a>
                @endcan
                @can('viewAny', \App\Models\Designation::class)
                    <a class="nav-link {{ request()->routeIs('masters.designations.*') ? 'active' : '' }}" href="{{ route('masters.designations.index') }}">
                        <i class="bi bi-person-badge me-2"></i>Designation Master
                    </a>
                @endcan
                @can('viewAny', \App\Models\EmployeeType::class)
                    <a class="nav-link {{ request()->routeIs('masters.employee-types.*') ? 'active' : '' }}" href="{{ route('masters.employee-types.index') }}">
                        <i class="bi bi-people-fill me-2"></i>Employee Type Master
                    </a>
                @endcan
            @endif

            @if(auth()->user()?->hasPermissionTo('contractor.view') || auth()->user()?->hasPermissionTo('contractor-engagement.view'))
                <div class="mt-3 pt-3 border-top border-white border-opacity-25 text-white-50 small">Contractor Management</div>
                @can('viewAny', \App\Models\Contractor::class)
                    <a class="nav-link {{ request()->routeIs('contractors.master.*') ? 'active' : '' }}" href="{{ route('contractors.master.index') }}">
                        <i class="bi bi-briefcase me-2"></i>Contractor Master
                    </a>
                @endcan
                @can('viewAny', \App\Models\ContractorBranchEngagement::class)
                    <a class="nav-link {{ request()->routeIs('contractors.engagements.*') ? 'active' : '' }}" href="{{ route('contractors.engagements.index') }}">
                        <i class="bi bi-file-earmark-text me-2"></i>Branch Engagements
                    </a>
                @endcan
            @endif

            <div class="mt-3 pt-3 border-top border-white border-opacity-25 text-white-50 small">Future Modules</div>
            <span class="nav-link disabled">Employee Management</span>
            <span class="nav-link disabled">Attendance Management</span>
            <span class="nav-link disabled">Leave and LOP</span>
            <span class="nav-link disabled">Payroll Management</span>
            <span class="nav-link disabled">Reports</span>
        </nav>
    </aside>

    <div class="app-content">
        <header class="topbar navbar navbar-expand-lg sticky-top px-3 px-md-4 py-3">
            <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-sidebar-toggle>
                <i class="bi bi-list"></i>
            </button>
            <div class="navbar-brand fw-semibold mb-0">{{ config('hrms.app_name') }}</div>

            <div class="ms-auto d-flex align-items-center gap-3">
                @if($currentUser?->isSuperAdministrator())
                    <form method="post" action="{{ route('branch-context.update') }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <select name="branch_selection" class="form-select form-select-sm" style="width: 260px" onchange="this.form.submit()">
                            <option value="all" @selected($branchContext->isAllBranchesSelected())>{{ config('hrms.branch_all_label') }}</option>
                            @foreach($allBranches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchContext->currentBranchId() === $branch->id)>{{ $branch->branch_name }}{{ $branch->isHeadOffice() ? ' (Head Office)' : '' }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        {{ $currentUser?->name }} <span class="text-muted">({{ $currentUser?->primaryRoleName() }})</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('password.change') }}">Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="container-fluid p-3 p-md-4">
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Please review the form below.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (form.dataset.submitted === 'true') {
                return;
            }
            form.dataset.submitted = 'true';
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = true;
                var spinner = button.querySelector('[data-loading-spinner]');
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
            });
        });
    });
</script>
@stack('scripts')
</body>
</html>
