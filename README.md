# Padmam Industries HRMS

A manufacturing HR and payroll management application for Padmam Industries, built with Laravel.

This repository currently contains the **Application Foundation** only — authentication, role/permission
management, multi-branch data isolation, audit logging, the base admin layout, and reusable UI components.
Business modules (employees, attendance, leave, payroll, contractors, etc.) have not been implemented yet;
see [Modules Intentionally Not Implemented Yet](#modules-intentionally-not-implemented-yet).

## 1. Project Overview

Padmam Industries HRMS is a multi-branch application. Each **Branch / Unit** is a logical data boundary —
staff, contractors, attendance, leave, and payroll records will all be scoped to the branch they belong to.
The foundation phase establishes the infrastructure every later module will build on:

- Session-based authentication with throttling, password reset, and forced re-authentication on logout
- A Branch master and a centralized **Active Branch Context** service
- Branch-level data isolation enforced server-side (not just hidden UI)
- Role and permission scaffolding with a Super Administrator override
- Audit logging for security-sensitive actions
- A responsive Bootstrap 5 admin layout and a reusable Blade component library
- Environment-based error pages (403/404/419/422/500/503)

## 2. Technology Stack

| Concern | Choice |
|---|---|
| Backend | Laravel 12 |
| Database | MySQL |
| Frontend | Laravel Blade |
| UI Framework | Bootstrap 5 (+ Bootstrap Icons) |
| Authentication | Laravel session-based auth |
| Testing | PHPUnit (Laravel's `php artisan test`) |
| Timezone | Asia/Kolkata |
| Date display format | DD-MM-YYYY |
| Default currency | INR |

No React, Vue, or a separate frontend application is used.

## 3. Local Installation

```bash
git clone <repository-url> hrms2026
cd hrms2026
composer install
npm install
```

## 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms2026
DB_USERNAME=root
DB_PASSWORD=

APP_TIMEZONE=Asia/Kolkata

HRMS_SUPER_ADMIN_EMAIL=admin@padmamindustries.test
HRMS_SUPER_ADMIN_PASSWORD=ChangeMe123!
HRMS_BRANCH_ADMIN_EMAIL=branch.admin@padmamindustries.test
HRMS_BRANCH_ADMIN_PASSWORD=ChangeMe123!
```

`.env` is git-ignored and must never contain values that are also committed to source control.

## 5. MySQL Database Setup

Create an empty database that matches `DB_DATABASE` in your `.env`:

```sql
CREATE DATABASE hrms2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 6. Migrations

```bash
php artisan migrate
```

To rebuild the schema from scratch (drops all tables first):

```bash
php artisan migrate:fresh
```

## 7. Seeders

```bash
php artisan db:seed
```

Or combine migration and seeding:

```bash
php artisan migrate:fresh --seed
```

The seeders create two branches, all roles/permissions, and two development users (see
[Development Login Credentials](#11-development-login-credentials)).

> **Important:** `DatabaseSeeder` intentionally does **not** use Laravel's `WithoutModelEvents` trait.
> Branch assignment relies on Eloquent's `creating`/`updating` events (see
> [`app/Support/Traits/BelongsToBranch.php`](app/Support/Traits/BelongsToBranch.php)) — disabling model
> events during seeding would silently leave every seeded record without a `branch_id`.

## 8. Asset Build Commands

```bash
npm run dev    # local development (Vite)
npm run build  # production build
```

## 9. Application Run Commands

```bash
php artisan serve
```

Or run the server, queue listener, log tailer, and Vite dev server together:

```bash
composer run dev
```

Visit `http://localhost:8000` (or the port `artisan serve` reports).

## 10. Test Commands

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), independent of your local
MySQL database, so `php artisan test` never touches your dev data.

## 11. Development Login Credentials

Seeded by `database/seeders/UserSeeder.php`, using the values in `.env` (`HRMS_SUPER_ADMIN_*` /
`HRMS_BRANCH_ADMIN_*`). Defaults if unset:

| Role | Email | Username | Password | Branch |
|---|---|---|---|---|
| Super Administrator | `admin@padmamindustries.test` | `superadmin` | `ChangeMe123!` | None (all branches) |
| Branch Administrator | `branch.admin@padmamindustries.test` | `branchadmin` | `ChangeMe123!` | Head Office (`HO`) |

**These are development-only credentials.** Change every `HRMS_*_PASSWORD` value before deploying to any
shared or production environment, and never commit real credentials to `.env`.

## 12. Branch-Context Architecture

Every authenticated request resolves an **active branch** server-side — never from client input. See
[`docs/application-foundation.md`](docs/application-foundation.md) for full details. In short:

- `App\Services\BranchContext` is the single source of truth (`currentBranchId()`, `currentBranch()`,
  `hasActiveBranch()`, `isAllBranchesSelected()`, `setBranch()`, `clearBranch()`).
- `SetActiveBranch` middleware re-syncs the context from the authenticated user on every request. For a
  normal user this always resolves to their own `branch_id`, regardless of any session/query/form
  tampering.
- Only a Super Administrator may call `BranchContextController::update` to switch branches or select
  "All Branches"; the request is authorized via `BranchSwitchRequest::authorize()`.
- `EnsureActiveBranch` middleware blocks write operations when no specific branch is selected (including
  for a Super Administrator under "All Branches").
- The `BelongsToBranch` trait (applied to branch-scoped models) auto-assigns `branch_id` from the active
  context on create, filters queries with a global scope, and rejects any client-supplied `branch_id`.

## 13. Role and Permission Architecture

- Roles: `super-administrator`, `hr-administrator`, `payroll-administrator`, `branch-administrator`,
  `management-user`, `employee-user` (see `config/hrms.php`).
- Permissions are grouped (Dashboard, Branch, User, Role, Permission, Employee, Contractor, Attendance,
  Leave, Payroll, Report, Audit Log, Application Settings) and seeded from `config('hrms.permission_groups')`.
- The Super Administrator has every permission seeded as literal role-permission rows **and** a
  `Gate::before` short-circuit (`AuthServiceProvider::boot()`) that grants every ability — belt and
  suspenders.
- Authorization is enforced with Laravel Policies (`app/Policies/*`) bound via `authorizeResource()` in
  controllers, plus Gate definitions for each permission slug. UI elements (menu items, role checkboxes)
  are hidden as a convenience only — every protected controller action re-checks authorization
  server-side.

## 14. Audit Logging Approach

`App\Services\AuditService::record()` writes to `audit_logs` and is used for: login, failed login, logout,
password change, branch switch, user create/update, branch create/update, and role/permission updates.
`AuditService::sanitize()` strips passwords, tokens, and session identifiers before anything is persisted —
sensitive values are never logged. See [`docs/application-foundation.md`](docs/application-foundation.md)
for the field list and how to add audit calls to future modules.

## 15. Important Security Notes

- Public self-registration is disabled; users are created only by an authorized administrator.
- Inactive users, and users whose assigned branch is inactive, cannot log in.
- Branch isolation is enforced server-side (global query scope + middleware), not by hiding UI elements.
- Only a Super Administrator can assign the Super Administrator role to another user — enforced in
  `UserStoreRequest`/`UserUpdateRequest`, not just hidden from the role picker.
- CSRF protection, output escaping, and mass-assignment protection (explicit field whitelists in
  controllers) are used throughout.
- `APP_DEBUG` must be `false` in any non-local environment so stack traces are never shown to end users.

## 16. Current Implementation Scope

Implemented: Laravel project structure, authentication (login/logout/forgot-password/reset/change
password), base admin layout, user & branch foundation tables, role/permission scaffolding, branch context
architecture and isolation, audit logging, reusable Blade components, environment-aware error pages, dev
seeders, and automated foundation tests.

## 17. Modules Intentionally Not Implemented Yet

Out of scope for this phase (per the Application Foundation brief) — no code for these exists yet:
Branch Management (full CRUD/UX beyond the minimal master), Department/Designation/Employee Type masters,
Employee Registration, Contractor Management, Shift Master and assignment, Attendance upload/biometric
integration, Leave/Permission/LOP, Temporary Department Assignments, Salary structure, PF/ESI/TDS
calculation, Overtime, Payroll generation/confirmation, Reports and exports, business dashboard
calculations, notifications, and approval workflows.
