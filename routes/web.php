<?php

use App\Http\Controllers\Administration\AuditLogController;
use App\Http\Controllers\Administration\BranchController;
use App\Http\Controllers\Administration\OrganizationController;
use App\Http\Controllers\Administration\RoleController;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'branch.context'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/profile/change-password', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/profile/change-password', [PasswordController::class, 'update'])->name('password.change.update');

    Route::post('/branch-context', [BranchContextController::class, 'update'])->name('branch-context.update');

    Route::get('/organization', [OrganizationController::class, 'show'])->name('organization.show');
    Route::get('/organization/edit', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])->name('organization.update');
    Route::post('/organization/logo', [OrganizationController::class, 'updateLogo'])->name('organization.logo.update');

    Route::resource('branches', BranchController::class)->except(['destroy']);
    Route::patch('/branches/{branch}/activate', [BranchController::class, 'activate'])->name('branches.activate');
    Route::patch('/branches/{branch}/inactivate', [BranchController::class, 'inactivate'])->name('branches.inactivate');
    Route::patch('/branches/{branch}/make-head-office', [BranchController::class, 'makeHeadOffice'])->name('branches.make-head-office');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('branch.active')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('branch.active')->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('branch.active')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('branch.active')->name('users.update');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
});
