<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\AuditLog::class => \App\Policies\AuditLogPolicy::class,
        \App\Models\Organization::class => \App\Policies\OrganizationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (?User $user): ?bool {
            if ($user && $user->isSuperAdministrator()) {
                return true;
            }

            return null;
        });

        foreach (collect(config('hrms.permission_groups', []))->flatten()->unique() as $permission) {
            Gate::define($permission, function (User $user) use ($permission): bool {
                return $user->hasPermissionTo($permission);
            });
        }
    }
}
