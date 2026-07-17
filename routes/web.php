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
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\DesignationController;
use App\Http\Controllers\Masters\SectionController;
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

    Route::prefix('masters')->name('masters.')->group(function (): void {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->middleware('branch.active')->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->middleware('branch.active')->name('departments.store');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->middleware('branch.active')->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->middleware('branch.active')->name('departments.update');
        Route::patch('/departments/{department}/activate', [DepartmentController::class, 'activate'])->middleware('branch.active')->name('departments.activate');
        Route::patch('/departments/{department}/inactivate', [DepartmentController::class, 'inactivate'])->middleware('branch.active')->name('departments.inactivate');
        Route::get('/departments/{department}/sections', [SectionController::class, 'byDepartment'])->name('departments.sections');

        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('/sections/create', [SectionController::class, 'create'])->middleware('branch.active')->name('sections.create');
        Route::post('/sections', [SectionController::class, 'store'])->middleware('branch.active')->name('sections.store');
        Route::get('/sections/{section}', [SectionController::class, 'show'])->name('sections.show');
        Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->middleware('branch.active')->name('sections.edit');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->middleware('branch.active')->name('sections.update');
        Route::patch('/sections/{section}/activate', [SectionController::class, 'activate'])->middleware('branch.active')->name('sections.activate');
        Route::patch('/sections/{section}/inactivate', [SectionController::class, 'inactivate'])->middleware('branch.active')->name('sections.inactivate');

        Route::get('/designations', [DesignationController::class, 'index'])->name('designations.index');
        Route::get('/designations/create', [DesignationController::class, 'create'])->middleware('branch.active')->name('designations.create');
        Route::post('/designations', [DesignationController::class, 'store'])->middleware('branch.active')->name('designations.store');
        Route::get('/designations/{designation}', [DesignationController::class, 'show'])->name('designations.show');
        Route::get('/designations/{designation}/edit', [DesignationController::class, 'edit'])->middleware('branch.active')->name('designations.edit');
        Route::put('/designations/{designation}', [DesignationController::class, 'update'])->middleware('branch.active')->name('designations.update');
        Route::patch('/designations/{designation}/activate', [DesignationController::class, 'activate'])->middleware('branch.active')->name('designations.activate');
        Route::patch('/designations/{designation}/inactivate', [DesignationController::class, 'inactivate'])->middleware('branch.active')->name('designations.inactivate');
    });
});
