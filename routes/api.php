<?php

use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\Admin\ArchiveController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeImportController;
use App\Http\Controllers\Admin\NumberingConfigController;
use App\Http\Controllers\Admin\ReferenceController;
use App\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Auth (no admin guard yet)
    Route::post('login',  [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me',      [AuthController::class, 'me'])->middleware('auth:sanctum');

    // Authenticated admin endpoints
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Reference / lookups
        Route::get('reference/opds',                [ReferenceController::class, 'opds']);
        Route::get('reference/jabatan-categories',  [ReferenceController::class, 'jabatanCategories']);
        Route::get('reference/appointment-years',   [ReferenceController::class, 'appointmentYears']);
        Route::get('reference/numberings',          [ReferenceController::class, 'activeNumberings']);
        Route::get('reference/templates',           [ReferenceController::class, 'activeTemplates']);
        Route::get('dashboard',                     [ReferenceController::class, 'dashboard']);

        // Employees
        Route::get('employees',           [EmployeeController::class, 'index']);
        Route::post('employees',          [EmployeeController::class, 'store']);
        Route::get('employees/{employee}',[EmployeeController::class, 'show']);
        Route::put('employees/{employee}',[EmployeeController::class, 'update']);
        Route::delete('employees/{employee}',[EmployeeController::class, 'destroy']);

        // Imports
        Route::get('imports',                  [EmployeeImportController::class, 'index']);
        Route::post('imports',                 [EmployeeImportController::class, 'store']);
        Route::get('imports/template',         [EmployeeImportController::class, 'template']);
        Route::get('imports/{batch}',          [EmployeeImportController::class, 'show']);

        // Templates
        Route::get('templates',                  [TemplateController::class, 'index']);
        Route::post('templates',                 [TemplateController::class, 'store']);
        Route::get('templates/placeholders',     [TemplateController::class, 'placeholders']);
        Route::get('templates/{template}',       [TemplateController::class, 'show']);
        Route::put('templates/{template}',       [TemplateController::class, 'update']);
        Route::delete('templates/{template}',    [TemplateController::class, 'destroy']);

        // Numbering
        Route::get('numbering',                  [NumberingConfigController::class, 'index']);
        Route::post('numbering',                 [NumberingConfigController::class, 'store']);
        Route::post('numbering/preview',         [NumberingConfigController::class, 'preview']);
        Route::get('numbering/{numbering}',      [NumberingConfigController::class, 'show']);
        Route::put('numbering/{numbering}',      [NumberingConfigController::class, 'update']);

        // Agreements
        Route::get('agreements',                       [AgreementController::class, 'index']);
        Route::post('agreements',                      [AgreementController::class, 'store']);
        Route::post('agreements/batch-extend',         [AgreementController::class, 'batchExtend']);
        Route::get('agreements/{agreement}',           [AgreementController::class, 'show']);
        Route::post('agreements/{agreement}/extend',   [AgreementController::class, 'extend']);
        Route::post('agreements/{agreement}/cancel',   [AgreementController::class, 'cancel']);
        Route::get('agreements/{agreement}/download',  [AgreementController::class, 'downloadPdf']);

        // Archive
        Route::get('archive',                         [ArchiveController::class, 'index']);

        // Audit logs
        Route::get('audit-logs',                      [AuditLogController::class, 'index']);
        Route::get('audit-logs-actions',              [AuditLogController::class, 'actions']);
    });
});
