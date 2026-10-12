<?php

use App\Http\Controllers\Internal\Auth\ForgotPasswordController;
use App\Http\Controllers\Internal\Auth\LoginController;
use App\Http\Controllers\Internal\Auth\ResetPasswordController;
use App\Http\Controllers\Internal\CommandCenter\CommandCenterController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\ExecutiveDashboard\AdminController as ExecAdminController;
use App\Http\Controllers\Internal\ExecutiveDashboard\DashboardController as ExecDashboardController;
use App\Http\Controllers\Internal\ExecutiveDashboard\ExportController as ExecExportController;
use App\Http\Controllers\Internal\Settings\AuditLogController;
use App\Http\Controllers\Internal\Users\PermissionController;
use App\Http\Controllers\Internal\Users\RoleController;
use App\Http\Controllers\Internal\Users\UserController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════
// MODULE 3: Internal Portal (Digital Workplace)
// Authenticated staff workspace — uses the default `web` session
// guard, with its own login/dashboard separate from the public site.
//
// This portal no longer edits website content. Everything editorial —
// news, announcements, alerts, knowledge articles, FAQs, forms, offices,
// the organisation chart, ITA, homepage text and images, the Smart Guide
// and assessment results — is now authored in WordPress and synced in by
// App\Services\Cms (see docs/wordpress-migration-plan.md).
//
// What remains here is the work WordPress has no business doing:
// operational statistics (Executive Dashboard), real-time operations
// (Command Center), the accounts that authorise them, and the audit log.
// ═══════════════════════════════════════════════════════════════

Route::prefix('internal')->name('internal.')->group(function () {

    // ── Guest-only auth routes ─────────────────────────────────
    Route::middleware('internal.guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    // ── Authenticated portal routes ────────────────────────────
    Route::middleware('internal.auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ── Executive Dashboard (Module 4: Executive Intelligence) ──
        Route::prefix('executive')->name('executive.')->group(function () {
            Route::get('/', [ExecDashboardController::class, 'index'])->name('index');

            // Export
            Route::prefix('export')->name('export.')->group(function () {
                Route::get('{period}/csv',   [ExecExportController::class, 'csv'])->name('csv');
                Route::get('{period}/print', [ExecExportController::class, 'print'])->name('print');
            });

            // Admin data management
            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('/',               [ExecAdminController::class, 'index'])->name('index');
                Route::get('/create',         [ExecAdminController::class, 'create'])->name('create');
                Route::post('/',              [ExecAdminController::class, 'store'])->name('store');
                Route::get('/{period}/edit',  [ExecAdminController::class, 'edit'])->name('edit');
                Route::put('/{period}',       [ExecAdminController::class, 'update'])->name('update');
                Route::delete('/{period}',    [ExecAdminController::class, 'destroy'])->name('destroy');
                Route::patch('/{period}/set-latest', [ExecAdminController::class, 'setLatest'])->name('set-latest');

                // Section-specific update endpoints
                Route::patch('/{period}/kpis',         [ExecAdminController::class, 'updateKpis'])->name('update-kpis');
                Route::patch('/{period}/nationalities', [ExecAdminController::class, 'updateNationalities'])->name('update-nationalities');
                Route::patch('/{period}/categories',   [ExecAdminController::class, 'updateCategories'])->name('update-categories');
                Route::patch('/{period}/trends',       [ExecAdminController::class, 'updateTrends'])->name('update-trends');
                Route::patch('/{period}/provinces',    [ExecAdminController::class, 'updateProvinces'])->name('update-provinces');
            });
        });

        // ── Command Center (Module 4: real-time operational monitoring) ──
        Route::prefix('command-center')->name('command-center.')
            ->middleware('command-center.permission:view')
            ->group(function () {
                Route::get('/', [CommandCenterController::class, 'index'])->name('index');
                Route::get('/data', [CommandCenterController::class, 'data'])->name('data');
                Route::get('/cameras', [CommandCenterController::class, 'cameras'])->name('cameras');
                Route::get('/alerts', [CommandCenterController::class, 'alerts'])->name('alerts');
                Route::get('/incidents', [CommandCenterController::class, 'incidents'])->name('incidents');
                Route::get('/offices', [CommandCenterController::class, 'offices'])->name('offices');

                Route::patch('/incidents/{incident}', [CommandCenterController::class, 'updateIncident'])
                    ->name('incidents.update')
                    ->middleware('command-center.permission:manage');
            });

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // ── Users & Permissions ──────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

    });
});
