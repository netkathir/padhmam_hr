# Application Foundation — Technical Notes

This document explains the reusable infrastructure built in the Application Foundation phase, so that
future business modules (employees, attendance, leave, payroll, contractors, etc.) can build on it
consistently.

## 1. How the Active Branch Is Resolved

`App\Services\BranchContext` (registered as a singleton in `AppServiceProvider`) is the single source of
truth for "what branch am I currently operating in." It stores state in the session under the
`hrms.active_branch` key (`config('hrms.branch_context_session_key')`) and exposes:

| Method | Purpose |
|---|---|
| `currentBranchId(): ?int` | The active branch ID, or `null` if none / "All Branches" is selected |
| `currentBranch(): ?Branch` | The active `Branch` model |
| `hasActiveBranch(): bool` | `true` only when a specific branch is selected (not "All Branches") |
| `isAllBranchesSelected(): bool` | Super Administrator's "All Branches" mode |
| `setBranch(Branch\|int $branch): void` | Selects a branch; throws if the branch is inactive |
| `setAllBranches(): void` | Enters "All Branches" mode (Super Administrator only, enforced by the caller) |
| `clearBranch(): void` | Clears the session branch state |
| `syncForUser(?User $user): void` | Resets the context from a user's own `branch_id` (or "All Branches" for a Super Administrator) |
| `withoutBranchContext(callable $callback): mixed` | Suspends enforcement for the duration of the callback (seeders, console commands) |
| `requireSpecificBranch(): void` | Throws a `ValidationException` unless a specific branch is active |

**The context is re-derived from the authenticated user on every request**, not trusted from client input:
`SetActiveBranch` middleware (aliased as `branch.context`) calls `syncForUser($request->user())` on every
request in the authenticated route group. For a normal user this always resolves to their own `branch_id`
— session tampering, query parameters, or hidden form fields cannot change it. Only a Super Administrator
can change branches, and only through `BranchContextController::update`, which is itself gated by
`BranchSwitchRequest::authorize()` (`$this->user()?->isSuperAdministrator()`).

## 2. How Branch Isolation Is Enforced

Enforcement happens at three layers, so hiding a UI element is never the only control:

1. **Global query scope** — `App\Support\Traits\BelongsToBranch` registers a global scope named `branch`
   on any model that uses it. Every query is automatically filtered to `branch_id = currentBranchId()`
   unless the context is bypassed or "All Branches" is selected.
2. **Auto-assignment on write** — the trait's `creating` hook assigns `branch_id` from the active context
   and throws if no specific branch is active. The `updating` hook only re-validates/reassigns
   `branch_id` when it is actually dirty, so unrelated updates (e.g. a login timestamp touch) never
   silently move a record to whatever branch happens to be selected.
3. **Route-model binding override** — `resolveRouteBinding()` on the trait looks up the model
   **without** the branch scope, so a request for a record in another branch is *found* (not hidden behind
   a generic 404) and can then be denied with a proper `403` by the model's Policy. This is what makes
   `GET /users/{other-branch-user}` return `403 Forbidden` instead of a `404` that would leak no
   information about whether the record exists.
4. **Middleware** — `EnsureActiveBranch` (aliased `branch.active`) is applied to write routes. For a
   normal user it aborts `403` if their `branch_id` doesn't match the active context, and `422` if no
   branch is active at all. For a Super Administrator it aborts `422` on write requests made while "All
   Branches" is selected — a write operation must always target one specific branch.

Any client-supplied `branch_id` in a create/update request body is **ignored**; the value is always taken
from `BranchContext`, never from `$request->input('branch_id')`.

## 3. How Super Administrator Branch Switching Works

- The admin layout header renders a branch selector only when `auth()->user()->isSuperAdministrator()` is
  true (`resources/views/layouts/admin.blade.php`).
- Submitting the selector posts to `branch-context.update`, handled by `BranchContextController::update`.
- `BranchSwitchRequest::authorize()` rejects the request outright for anyone who isn't a Super
  Administrator.
- The controller resolves the branch by ID and calls `BranchContext::setBranch()`, which itself refuses to
  select an inactive branch (`RuntimeException`, surfaced as a `404` at the route level in this flow's
  tests).
- The switch is recorded via `AuditService::record('branch_switch', ...)`.

## 4. How Future Branch-Scoped Models Should Use the Trait

Add `use App\Support\Traits\BelongsToBranch;` to any model representing branch-scoped business data
(departments, designations, employees, contractors, shifts, attendance, leave, payroll, etc.) and add a
`branch_id` foreign key column (nullable is not needed — branch-scoped business records always require a
specific branch):

```php
Schema::create('departments', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('status')->default('active')->index();
    $table->timestamps();
});
```

```php
class Department extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'name', 'status'];
}
```

Do not manually filter these models by branch in controllers — the trait's global scope does it
automatically. Do not accept `branch_id` from request input for these models; let the trait assign it.

> **Caution:** do not chain `->index()` directly after `->constrained()->nullOnDelete()` (or any other
> foreign-key modifier) in a migration. `constrained()` returns the foreign-key command object, not the
> column definition, so a trailing `->index()` sets a stray `index` attribute on that command instead of
> adding a real index — which Laravel's grammar then misuses as the constraint name, producing broken
> (and colliding) constraint names. A constrained foreign-key column is already indexed by MySQL/MariaDB
> automatically; no separate `->index()` call is needed.

## 5. How Console Commands and Jobs Should Establish Branch Context

Artisan commands, queued jobs, and seeders have no authenticated user and no HTTP session, so
`BranchContext` has nothing to sync from. Two options:

- **Bypass entirely** (for cross-branch administrative operations, e.g. creating the Super Administrator):

  ```php
  app(BranchContext::class)->withoutBranchContext(function () {
      // creating/updating branch-scoped models here skips the "specific branch required" check
      // and does NOT auto-assign branch_id — set branch_id explicitly if the model needs one
  });
  ```

- **Set an explicit branch** (for a branch-specific batch job):

  ```php
  app(BranchContext::class)->setBranch($branch);
  // ... do work ...
  app(BranchContext::class)->clearBranch();
  ```

Never introduce an unconditional/public bypass (e.g. a config flag that disables branch scoping
application-wide) — bypassing is only ever done explicitly, per call site, by code that has already
decided it's operating outside normal branch boundaries.

## 6. How Permissions Should Be Added

1. Add the permission slug to the relevant group in `config('hrms.permission_groups')` — group names and
   the slug format (`module.action`, e.g. `employee.export`) should match the existing convention.
2. Run `php artisan db:seed --class=RolePermissionSeeder` (or `migrate:fresh --seed` in dev) to persist it
   and assign it to whichever roles need it in `RolePermissionSeeder::$assignments`.
3. `AuthServiceProvider::boot()` automatically registers a `Gate::define()` for every permission slug found
   in the config, so `$user->can('employee.export')` / `@can('employee.export')` works without further
   wiring. The Super Administrator gets every ability via `Gate::before()` regardless of explicit
   assignment.
4. For model-specific authorization (view/update a specific record, not just "can this role do X at all"),
   add a Policy and register it in `AuthServiceProvider::$policies`, then call `$this->authorizeResource()`
   in the controller constructor (see `UserController`, `BranchController`, `RoleController` for the
   pattern) so every resource action is checked server-side.

## 7. How Audit Logs Should Be Recorded

Inject `App\Services\AuditService` and call `record()`:

```php
$this->auditService->record(
    event: 'department_create',   // short, snake_case event name
    module: 'department',         // module/feature grouping
    auditable: $department,       // the model instance (or null for non-model events)
    oldValues: [],                // [] for create; previous attributes for update
    newValues: $department->toArray(),
    request: $request,            // captures ip/user-agent/route/method
);
```

`AuditService::sanitize()` automatically strips `password`, `password_confirmation`, `current_password`,
`new_password`, `remember_token`, `token`, `reset_token`, `session_id`, `api_token`, and `secret` keys from
both `old_values` and `new_values` before persisting — never bypass `sanitize()` by writing to `AuditLog`
directly. If a new module introduces its own sensitive fields (e.g. a bank account number), extend
`AuditService::SENSITIVE_KEYS` rather than filtering ad hoc in the controller.

`audit_logs.branch_id` and `audit_logs.user_id` are populated automatically from the current
`BranchContext`/authenticated user — callers only need to supply the event-specific data.
