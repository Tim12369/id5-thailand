<?php

use App\Http\Controllers\Internal\Auth\ForgotPasswordController;
use App\Http\Controllers\Internal\Auth\LoginController;
use App\Http\Controllers\Internal\Auth\ResetPasswordController;
use App\Http\Controllers\Internal\Cms\AlertController;
use App\Http\Controllers\Internal\Cms\AnnouncementController;
use App\Http\Controllers\Internal\Cms\CmsDashboardController;
use App\Http\Controllers\Internal\Cms\KnowledgeBaseController;
use App\Http\Controllers\Internal\Cms\NewsController;
use App\Http\Controllers\Internal\CommandCenter\CommandCenterController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\ExecutiveDashboard\AdminController as ExecAdminController;
use App\Http\Controllers\Internal\ExecutiveDashboard\DashboardController as ExecDashboardController;
use App\Http\Controllers\Internal\ExecutiveDashboard\ExportController as ExecExportController;
use App\Http\Controllers\Internal\Faq\FaqController;
use App\Http\Controllers\Internal\Ita\ItaAdminController;
use App\Http\Controllers\Internal\Media\MediaLibraryController;
use App\Http\Controllers\Internal\OrgChart\OrgChartController;
use App\Http\Controllers\Internal\Settings\AppearanceController;
use App\Http\Controllers\Internal\Settings\AuditLogController;
use App\Http\Controllers\Internal\Settings\GeneralSettingsController;
use App\Http\Controllers\Internal\Settings\LanguageController;
use App\Http\Controllers\Internal\Settings\SiteSettingsController;
use App\Http\Controllers\Internal\SmartGuide\GuideTopicController;
use App\Http\Controllers\Internal\Assessment\AssessmentRecommendationController;
use App\Http\Controllers\Internal\SmartGuide\SmartGuideContentController;
use App\Http\Controllers\Internal\Users\PermissionController;
use App\Http\Controllers\Internal\Users\RoleController;
use App\Http\Controllers\Internal\Users\UserController;
use App\Http\Controllers\Internal\Website\AboutDocumentController;
use App\Http\Controllers\Internal\Website\ContactController as WebsiteContactController;
use App\Http\Controllers\Internal\Website\DivisionCardController;
use App\Http\Controllers\Internal\Website\HomepageController;
use App\Http\Controllers\Internal\Website\PublicFormController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════
// MODULE 3: Internal Portal (Digital Workplace)
// Authenticated staff workspace — uses the default `web` session
// guard, with its own login/dashboard separate from the public site.
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

        // ── Content Management System (public homepage content) ────
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('/', [CmsDashboardController::class, 'index'])->name('dashboard');

            Route::resource('news', NewsController::class)->except(['show'])->parameters(['news' => 'article']);
            Route::resource('announcements', AnnouncementController::class)->except(['show'])->parameters(['announcements' => 'article']);
            Route::resource('alerts', AlertController::class)->except(['show'])->parameters(['alerts' => 'article']);
            Route::resource('knowledge-base', KnowledgeBaseController::class)->except(['show'])->parameters(['knowledge-base' => 'article']);

            Route::patch('news/{article}/toggle', [NewsController::class, 'togglePublish'])->name('news.toggle');
            Route::patch('announcements/{article}/toggle', [AnnouncementController::class, 'togglePublish'])->name('announcements.toggle');
            Route::patch('alerts/{article}/toggle', [AlertController::class, 'togglePublish'])->name('alerts.toggle');
            Route::patch('knowledge-base/{article}/toggle', [KnowledgeBaseController::class, 'togglePublish'])->name('knowledge-base.toggle');
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

        // ── Site Settings (homepage hero image, etc.) ───────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SiteSettingsController::class, 'edit'])->name('edit');
            Route::post('/hero-image', [SiteSettingsController::class, 'updateHeroImage'])->name('hero-image.update');
            Route::delete('/hero-image', [SiteSettingsController::class, 'destroyHeroImage'])->name('hero-image.destroy');
        });

        // ── Settings: Website Settings / Appearance / Languages / Audit Logs ──
        Route::prefix('website-settings')->name('website-settings.')->group(function () {
            Route::get('/', [GeneralSettingsController::class, 'edit'])->name('index');
            Route::put('/', [GeneralSettingsController::class, 'update'])->name('update');
        });

        Route::prefix('appearance')->name('appearance.')->group(function () {
            Route::get('/', [AppearanceController::class, 'edit'])->name('index');
            Route::put('/', [AppearanceController::class, 'update'])->name('update');
        });

        Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // ── Organization Chart (ตร. / สตม. / บก.ตม.5 leadership charts) ──
        Route::prefix('org-chart')->name('org-chart.')->group(function () {
            Route::get('/', [OrgChartController::class, 'index'])->name('index');

            Route::post('/members', [OrgChartController::class, 'storeMember'])->name('members.store');
            Route::post('/members/{member}', [OrgChartController::class, 'updateMember'])->name('members.update');
            Route::delete('/members/{member}', [OrgChartController::class, 'destroyMember'])->name('members.destroy');
        });

        // ── ITA Management (Integrity & Transparency Assessment page) ──
        Route::prefix('ita')->name('ita.')->group(function () {
            Route::get('/', [ItaAdminController::class, 'index'])->name('index');

            Route::get('/sections/create', [ItaAdminController::class, 'createSection'])->name('sections.create');
            Route::post('/sections', [ItaAdminController::class, 'storeSection'])->name('sections.store');
            Route::get('/sections/{itaSection}/edit', [ItaAdminController::class, 'editSection'])->name('sections.edit');
            Route::put('/sections/{itaSection}', [ItaAdminController::class, 'updateSection'])->name('sections.update');
            Route::delete('/sections/{itaSection}', [ItaAdminController::class, 'destroySection'])->name('sections.destroy');

            Route::get('/cards/create', [ItaAdminController::class, 'createCard'])->name('cards.create');
            Route::post('/cards', [ItaAdminController::class, 'storeCard'])->name('cards.store');
            Route::get('/cards/{itaCard}/edit', [ItaAdminController::class, 'editCard'])->name('cards.edit');
            Route::put('/cards/{itaCard}', [ItaAdminController::class, 'updateCard'])->name('cards.update');
            Route::delete('/cards/{itaCard}', [ItaAdminController::class, 'destroyCard'])->name('cards.destroy');
        });

        // ── FAQ ──────────────────────────────────────────────────────
        Route::prefix('faq')->name('faq.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::get('/create', [FaqController::class, 'create'])->name('create');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::get('/{faq}/edit', [FaqController::class, 'edit'])->name('edit');
            Route::put('/{faq}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [FaqController::class, 'destroy'])->name('destroy');
        });

        // ── Smart Guide: Topics (icon/deadline + shared documents/steps/locations) ──
        Route::prefix('smart-guide-topics')->name('smart-guide-topics.')->group(function () {
            Route::get('/', [GuideTopicController::class, 'index'])->name('index');
            Route::get('/create', [GuideTopicController::class, 'create'])->name('create');
            Route::post('/', [GuideTopicController::class, 'store'])->name('store');
            Route::get('/{guideTopic}/edit', [GuideTopicController::class, 'edit'])->name('edit');
            Route::put('/{guideTopic}', [GuideTopicController::class, 'update'])->name('update');
            Route::delete('/{guideTopic}', [GuideTopicController::class, 'destroy'])->name('destroy');

            Route::post('/{guideTopic}/documents', [GuideTopicController::class, 'storeDocument'])->name('documents.store');
            Route::put('/documents/{document}', [GuideTopicController::class, 'updateDocument'])->name('documents.update');
            Route::delete('/documents/{document}', [GuideTopicController::class, 'destroyDocument'])->name('documents.destroy');

            Route::post('/{guideTopic}/steps', [GuideTopicController::class, 'storeStep'])->name('steps.store');
            Route::put('/steps/{step}', [GuideTopicController::class, 'updateStep'])->name('steps.update');
            Route::delete('/steps/{step}', [GuideTopicController::class, 'destroyStep'])->name('steps.destroy');

            Route::post('/{guideTopic}/locations', [GuideTopicController::class, 'storeLocation'])->name('locations.store');
            Route::put('/locations/{location}', [GuideTopicController::class, 'updateLocation'])->name('locations.update');
            Route::delete('/locations/{location}', [GuideTopicController::class, 'destroyLocation'])->name('locations.destroy');
        });

        // ── Smart Guide: per-persona×topic SW1H content matrix ─────────
        Route::prefix('smart-guide-content')->name('smart-guide-content.')->group(function () {
            Route::get('/', [SmartGuideContentController::class, 'index'])->name('index');
            Route::get('/{persona}/{topic}/edit', [SmartGuideContentController::class, 'edit'])
                ->name('edit')->where(['persona' => '[a-z_]+', 'topic' => '[a-z0-9_]+']);
            Route::put('/{persona}/{topic}', [SmartGuideContentController::class, 'update'])
                ->name('update')->where(['persona' => '[a-z_]+', 'topic' => '[a-z0-9_]+']);
        });

        // Guided Assessment — result content per inferred status (edit-only).
        Route::prefix('assessment-content')->name('assessment-content.')->group(function () {
            Route::get('/', [AssessmentRecommendationController::class, 'index'])->name('index');
            Route::get('/{status}/edit', [AssessmentRecommendationController::class, 'edit'])
                ->name('edit')->where('status', '[a-z_]+');
            Route::put('/{status}', [AssessmentRecommendationController::class, 'update'])
                ->name('update')->where('status', '[a-z_]+');
        });

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

        // ── Website Management ────────────────────────────────────────
        Route::prefix('website-homepage')->name('website-homepage.')->group(function () {
            Route::get('/', [HomepageController::class, 'edit'])->name('index');
            Route::put('/', [HomepageController::class, 'update'])->name('update');
        });

        Route::prefix('website-divisions')->name('website-divisions.')->group(function () {
            Route::get('/', [DivisionCardController::class, 'index'])->name('index');
            Route::get('/{homeDivisionCard}/edit', [DivisionCardController::class, 'edit'])->name('edit');
            Route::put('/{homeDivisionCard}', [DivisionCardController::class, 'update'])->name('update');
        });

        Route::prefix('website-contact')->name('website-contact.')->group(function () {
            Route::get('/', [WebsiteContactController::class, 'index'])->name('index');
            Route::get('/create', [WebsiteContactController::class, 'create'])->name('create');
            Route::post('/', [WebsiteContactController::class, 'store'])->name('store');
            Route::get('/import', [WebsiteContactController::class, 'import'])->name('import');
            Route::post('/import', [WebsiteContactController::class, 'importStore'])->name('import.store');
            Route::get('/{station}/edit', [WebsiteContactController::class, 'edit'])->name('edit');
            Route::put('/{station}', [WebsiteContactController::class, 'update'])->name('update');
            Route::delete('/{station}', [WebsiteContactController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('website-forms')->name('website-forms.')->group(function () {
            Route::get('/', [PublicFormController::class, 'index'])->name('index');
            Route::get('/{publicForm}/edit', [PublicFormController::class, 'edit'])->name('edit');
            Route::put('/{publicForm}', [PublicFormController::class, 'update'])->name('update');
        });

        Route::prefix('website-about-documents')->name('website-about-documents.')->group(function () {
            Route::get('/', [AboutDocumentController::class, 'index'])->name('index');
            Route::get('/create', [AboutDocumentController::class, 'create'])->name('create');
            Route::post('/', [AboutDocumentController::class, 'store'])->name('store');
            Route::get('/{aboutDocument}/edit', [AboutDocumentController::class, 'edit'])->name('edit');
            Route::put('/{aboutDocument}', [AboutDocumentController::class, 'update'])->name('update');
            Route::delete('/{aboutDocument}', [AboutDocumentController::class, 'destroy'])->name('destroy');
        });

        // ── Media Library ────────────────────────────────────────────
        Route::prefix('media-library')->name('media-library.')->group(function () {
            Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
            Route::post('/', [MediaLibraryController::class, 'store'])->name('store');
            Route::put('/{medium}', [MediaLibraryController::class, 'update'])->name('update');
            Route::delete('/{medium}', [MediaLibraryController::class, 'destroy'])->name('destroy');

            Route::post('/folders', [MediaLibraryController::class, 'storeFolder'])->name('folders.store');
            Route::delete('/folders/{folder}', [MediaLibraryController::class, 'destroyFolder'])->name('folders.destroy');
        });

    });
});
