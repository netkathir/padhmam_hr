<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureActiveBranch;
use App\Http\Middleware\SetActiveBranch;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'branch.active' => EnsureActiveBranch::class,
            'branch.context' => SetActiveBranch::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('employee-shifts:sync-statuses')
            ->dailyAt('00:15')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping();
    })
    ->create();
