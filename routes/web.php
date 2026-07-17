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
use App\Http\Controllers\Contractors\ContractorController;
use App\Http\Controllers\Contractors\ContractorDocumentController;
use App\Http\Controllers\Contractors\ContractorEngagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeNumbering\EmployeeNumberRuleController;
use App\Http\Controllers\EmployeeNumbering\EmployeeNumberSequenceController;
use App\Http\Controllers\Employees\EmployeeController;
use App\Http\Controllers\Employees\EmployeeDocumentController;
use App\Http\Controllers\Employees\EmployeeRegistrationController;
use App\Http\Controllers\Employees\EmployeeSeparationController;
use App\Http\Controllers\Employees\EmployeeStatusController;
use App\Http\Controllers\EmployeeShifts\EmployeeShiftAssignmentController;
use App\Http\Controllers\EmployeeShifts\EmployeeShiftChangeController;
use App\Http\Controllers\EmployeeShifts\EmployeeShiftHistoryController;
use App\Http\Controllers\EmployeeShifts\EmployeeShiftPendingController;
use App\Http\Controllers\EmployeeShifts\EmployeeTemporaryShiftController;
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\DesignationController;
use App\Http\Controllers\Masters\EmployeeTypeController;
use App\Http\Controllers\Masters\SectionController;
use App\Http\Controllers\Shifts\ShiftController;
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

        // Employee Types are organization-level records (not branch-scoped),
        // so these routes intentionally do not use the branch.active
        // middleware — they must remain accessible with "All Branches" selected.
        Route::get('/employee-types', [EmployeeTypeController::class, 'index'])->name('employee-types.index');
        Route::get('/employee-types/{employeeType}', [EmployeeTypeController::class, 'show'])->name('employee-types.show');
        Route::get('/employee-types/{employeeType}/edit', [EmployeeTypeController::class, 'edit'])->name('employee-types.edit');
        Route::put('/employee-types/{employeeType}', [EmployeeTypeController::class, 'update'])->name('employee-types.update');
    });

    Route::prefix('contractors')->name('contractors.')->group(function (): void {
        // Contractor profiles are organization-level records (not branch-scoped),
        // so these routes intentionally do not use the branch.active middleware.
        Route::resource('master', ContractorController::class)
            ->parameters(['master' => 'contractor'])
            ->except(['destroy']);
        Route::patch('/master/{contractor}/activate', [ContractorController::class, 'activate'])->name('master.activate');
        Route::patch('/master/{contractor}/inactivate', [ContractorController::class, 'inactivate'])->name('master.inactivate');

        Route::post('/master/{contractor}/documents', [ContractorDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/download', [ContractorDocumentController::class, 'download'])->name('documents.download');
        Route::patch('/documents/{document}/inactivate', [ContractorDocumentController::class, 'inactivate'])->name('documents.inactivate');

        Route::get('/engagements', [ContractorEngagementController::class, 'index'])->name('engagements.index');
        Route::get('/engagements/create', [ContractorEngagementController::class, 'create'])->middleware('branch.active')->name('engagements.create');
        Route::post('/engagements', [ContractorEngagementController::class, 'store'])->middleware('branch.active')->name('engagements.store');
        Route::get('/engagements/{engagement}', [ContractorEngagementController::class, 'show'])->name('engagements.show');
        Route::get('/engagements/{engagement}/edit', [ContractorEngagementController::class, 'edit'])->middleware('branch.active')->name('engagements.edit');
        Route::put('/engagements/{engagement}', [ContractorEngagementController::class, 'update'])->middleware('branch.active')->name('engagements.update');
        Route::patch('/engagements/{engagement}/activate', [ContractorEngagementController::class, 'activate'])->middleware('branch.active')->name('engagements.activate');
        Route::patch('/engagements/{engagement}/inactivate', [ContractorEngagementController::class, 'inactivate'])->middleware('branch.active')->name('engagements.inactivate');
    });

    Route::prefix('employee-numbering')->name('employee-numbering.')->group(function (): void {
        Route::get('/rules', [EmployeeNumberRuleController::class, 'index'])->name('rules.index');
        Route::get('/rules/create', [EmployeeNumberRuleController::class, 'create'])->middleware('branch.active')->name('rules.create');
        Route::post('/rules', [EmployeeNumberRuleController::class, 'store'])->middleware('branch.active')->name('rules.store');
        Route::post('/rules/preview', [EmployeeNumberRuleController::class, 'preview'])->middleware('branch.active')->name('rules.preview');
        Route::get('/rules/{rule}', [EmployeeNumberRuleController::class, 'show'])->name('rules.show');
        Route::get('/rules/{rule}/edit', [EmployeeNumberRuleController::class, 'edit'])->middleware('branch.active')->name('rules.edit');
        Route::put('/rules/{rule}', [EmployeeNumberRuleController::class, 'update'])->middleware('branch.active')->name('rules.update');
        Route::patch('/rules/{rule}/activate', [EmployeeNumberRuleController::class, 'activate'])->middleware('branch.active')->name('rules.activate');
        Route::patch('/rules/{rule}/inactivate', [EmployeeNumberRuleController::class, 'inactivate'])->middleware('branch.active')->name('rules.inactivate');
        Route::post('/rules/{rule}/new-version', [EmployeeNumberRuleController::class, 'createVersion'])->middleware('branch.active')->name('rules.new-version');

        Route::get('/sequences', [EmployeeNumberSequenceController::class, 'index'])->name('sequences.index');
        Route::get('/sequences/{sequence}', [EmployeeNumberSequenceController::class, 'show'])->name('sequences.show');
        Route::patch('/sequences/{sequence}/adjust', [EmployeeNumberSequenceController::class, 'adjust'])->middleware('branch.active')->name('sequences.adjust');
    });

    Route::prefix('shifts')->name('shifts.')->group(function (): void {
        Route::get('/master', [ShiftController::class, 'index'])->name('master.index');
        Route::get('/master/create', [ShiftController::class, 'create'])->middleware('branch.active')->name('master.create');
        Route::post('/master', [ShiftController::class, 'store'])->middleware('branch.active')->name('master.store');
        Route::get('/master/{shift}', [ShiftController::class, 'show'])->name('master.show');
        Route::get('/master/{shift}/edit', [ShiftController::class, 'edit'])->middleware('branch.active')->name('master.edit');
        Route::put('/master/{shift}', [ShiftController::class, 'update'])->middleware('branch.active')->name('master.update');
        Route::patch('/master/{shift}/activate', [ShiftController::class, 'activate'])->middleware('branch.active')->name('master.activate');
        Route::patch('/master/{shift}/inactivate', [ShiftController::class, 'inactivate'])->middleware('branch.active')->name('master.inactivate');
        Route::post('/master/{shift}/clone', [ShiftController::class, 'clone'])->middleware('branch.active')->name('master.clone');
    });

    Route::prefix('employee-shifts')->name('employee-shifts.')->group(function (): void {
        Route::get('/', [EmployeeShiftAssignmentController::class, 'index'])->name('index');
        Route::get('/pending', [EmployeeShiftPendingController::class, 'index'])->name('pending');

        Route::get('/{employee}/history', [EmployeeShiftHistoryController::class, 'index'])->name('history');

        Route::get('/{employee}/assign', [EmployeeShiftAssignmentController::class, 'create'])->middleware('branch.active')->name('create');
        Route::post('/{employee}/assign', [EmployeeShiftAssignmentController::class, 'store'])->middleware('branch.active')->name('store');

        Route::get('/{employee}/change', [EmployeeShiftChangeController::class, 'create'])->middleware('branch.active')->name('change.create');
        Route::post('/{employee}/change', [EmployeeShiftChangeController::class, 'store'])->middleware('branch.active')->name('change.store');

        Route::get('/{employee}/temporary', [EmployeeTemporaryShiftController::class, 'create'])->middleware('branch.active')->name('temporary.create');
        Route::post('/{employee}/temporary', [EmployeeTemporaryShiftController::class, 'store'])->middleware('branch.active')->name('temporary.store');

        Route::get('/{assignment}/edit', [EmployeeShiftAssignmentController::class, 'edit'])->middleware('branch.active')->name('edit');
        Route::put('/{assignment}', [EmployeeShiftAssignmentController::class, 'update'])->middleware('branch.active')->name('update');
        Route::patch('/{assignment}/cancel', [EmployeeShiftAssignmentController::class, 'cancel'])->middleware('branch.active')->name('cancel');
        Route::get('/{assignment}', [EmployeeShiftAssignmentController::class, 'show'])->name('show');
    });

    Route::prefix('employees')->name('employees.')->group(function (): void {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');

        Route::get('/create', [EmployeeRegistrationController::class, 'create'])->middleware('branch.active')->name('create');
        Route::post('/draft', [EmployeeRegistrationController::class, 'storeDraft'])->middleware('branch.active')->name('draft.store');

        // Dynamic dropdown / lookup endpoints (spec section 58) — registered
        // before the {employee} routes below so "lookup" is never mistaken
        // for a bound Employee identifier.
        Route::get('/lookup/departments/{department}/sections', [EmployeeRegistrationController::class, 'sectionsByDepartment'])->name('lookup.sections');
        Route::get('/lookup/designations', [EmployeeRegistrationController::class, 'designationsByScope'])->name('lookup.designations');
        Route::get('/lookup/reporting-managers', [EmployeeRegistrationController::class, 'reportingManagers'])->name('lookup.reporting-managers');
        Route::get('/lookup/contractors', [EmployeeRegistrationController::class, 'eligibleContractors'])->name('lookup.contractors');
        Route::get('/lookup/contractors/{contractor}/engagement', [EmployeeRegistrationController::class, 'contractorEngagementDetails'])->name('lookup.contractor-engagement');
        Route::get('/lookup/fixed-shifts', [EmployeeRegistrationController::class, 'eligibleFixedShifts'])->name('lookup.fixed-shifts');
        Route::get('/lookup/employee-number-preview', [EmployeeRegistrationController::class, 'employeeNumberPreview'])->name('lookup.employee-number-preview');

        Route::put('/{employee}/draft', [EmployeeRegistrationController::class, 'updateDraft'])->middleware('branch.active')->name('draft.update');
        Route::get('/{employee}/review', [EmployeeRegistrationController::class, 'review'])->name('review');
        Route::post('/{employee}/complete-registration', [EmployeeRegistrationController::class, 'complete'])->middleware('branch.active')->name('complete-registration');

        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('branch.active')->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->middleware('branch.active')->name('update');

        Route::patch('/{employee}/activate', [EmployeeStatusController::class, 'activate'])->middleware('branch.active')->name('activate');
        Route::patch('/{employee}/inactivate', [EmployeeStatusController::class, 'inactivate'])->middleware('branch.active')->name('inactivate');
        Route::patch('/{employee}/reactivate', [EmployeeStatusController::class, 'reactivate'])->middleware('branch.active')->name('reactivate');

        Route::post('/{employee}/separate', [EmployeeSeparationController::class, 'store'])->middleware('branch.active')->name('separate');

        Route::post('/{employee}/documents', [EmployeeDocumentController::class, 'store'])->middleware('branch.active')->name('documents.store');
        Route::get('/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
        Route::post('/documents/{document}/replace', [EmployeeDocumentController::class, 'replace'])->middleware('branch.active')->name('documents.replace');
        Route::patch('/documents/{document}/inactivate', [EmployeeDocumentController::class, 'inactivate'])->middleware('branch.active')->name('documents.inactivate');
    });
});
