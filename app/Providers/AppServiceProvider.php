<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\BranchContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BranchContext::class);
        $this->app->singleton(AuditService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone'));
    }
}
