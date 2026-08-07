<?php

use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\AiAssistantController;
use App\Http\Controllers\Public\AssessmentController;
use App\Http\Controllers\Public\AlertController;
use App\Http\Controllers\Public\AnnouncementController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EmergencyController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\FormDownloadController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ItaController;
use App\Http\Controllers\Public\KnowledgeBaseController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\OfficeController;
use App\Http\Controllers\Public\OpenDataController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\DivisionPageController;
use App\Http\Controllers\Public\SmartGuideController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════
// MODULE 1: PUBLIC PORTAL — no authentication required
// Middleware: web session, locale detection, security headers
// ═══════════════════════════════════════════════════════════════

Route::middleware(['web', 'set.locale'])
    ->name('public.')
    ->group(function () {

        // ── Homepage ───────────────────────────────────────────────
        Route::get('/', [HomeController::class, 'index'])->name('home');

        // ── About ──────────────────────────────────────────────────
        Route::get('/about',     [AboutController::class, 'index'])->name('about');
        Route::get('/ecosystem', [AboutController::class, 'ecosystem'])->name('ecosystem');

        // ── Division pages — each reachable from the homepage grid, all
        // sharing one controller/view (see DivisionPageController) ────
        Route::get('/technology-division', [DivisionPageController::class, 'index'])
            ->name('technology-division')->defaults('orgSlug', 'technology_division')->defaults('lang', 'technology_division');
        Route::get('/administrative-affairs-division', [DivisionPageController::class, 'index'])
            ->name('administration-division')->defaults('orgSlug', 'administration_division')->defaults('lang', 'administration_division');
        Route::get('/foreigner-services-division', [DivisionPageController::class, 'index'])
            ->name('foreigner-division')->defaults('orgSlug', 'foreigner_division')->defaults('lang', 'foreigner_division');
        Route::get('/investigation-division', [DivisionPageController::class, 'index'])
            ->name('investigation-division')->defaults('orgSlug', 'investigation_division')->defaults('lang', 'investigation_division');

        // ── Immigration Services ───────────────────────────────────
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/',          [ServiceController::class, 'index'])->name('index');
            Route::get('/visa',      [ServiceController::class, 'visa'])->name('visa');
            Route::get('/tm30',      [ServiceController::class, 'tm30'])->name('tm30');
            Route::get('/90-day',    [ServiceController::class, 'ninetyDay'])->name('90day');
            Route::get('/extension', [ServiceController::class, 'extension'])->name('extension');
            Route::get('/overstay',  [ServiceController::class, 'overstay'])->name('overstay');
        });

        // ── Guided Assessment Wizard (Who Are You?) ────────────────
        Route::prefix('assessment')->name('assessment.')->group(function () {
            Route::get('/', [AssessmentController::class, 'start'])->name('start');
            Route::post('/result', [AssessmentController::class, 'result'])->name('result');
        });

        // ── Smart Guide (SW1H) ─────────────────────────────────────
        Route::prefix('smart-guide')->name('smart-guide.')->group(function () {
            Route::get('/', [SmartGuideController::class, 'index'])->name('index');
            Route::get('/search', [SmartGuideController::class, 'search'])->name('search');
            Route::get('/{persona}/{topic}', [SmartGuideController::class, 'show'])
                ->name('show')
                ->where(['persona' => '[a-z_]+', 'topic' => '[a-z0-9_]+']);
        });

        // ── Immigration Office Locator ─────────────────────────────
        Route::prefix('offices')->name('offices.')->group(function () {
            Route::get('/', [OfficeController::class, 'index'])->name('index');
            Route::get('/{station}', [OfficeController::class, 'show'])->name('show');
        });

        // ── Public Knowledge Base ──────────────────────────────────
        Route::prefix('knowledge')->name('knowledge.')->group(function () {
            Route::get('/',       [KnowledgeBaseController::class, 'index'])->name('index');
            Route::get('/{slug}', [KnowledgeBaseController::class, 'show'])->name('show');
        });

        // ── News ───────────────────────────────────────────────────
        Route::prefix('news')->name('news.')->group(function () {
            Route::get('/',       [NewsController::class, 'index'])->name('index');
            Route::get('/{slug}', [NewsController::class, 'show'])->name('show');
        });

        // ── Announcements ──────────────────────────────────────────
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/',       [AnnouncementController::class, 'index'])->name('index');
            Route::get('/{slug}', [AnnouncementController::class, 'show'])->name('show');
        });

        // ── Alerts ─────────────────────────────────────────────────
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');

        // ── FAQ ────────────────────────────────────────────────────
        Route::get('/faq', [FaqController::class, 'index'])->name('faq');

        // ── Downloadable Forms ─────────────────────────────────────
        Route::prefix('downloads')->name('downloads.')->group(function () {
            Route::get('/', [FormDownloadController::class, 'index'])->name('index');
            Route::get('/{id}/download', [FormDownloadController::class, 'download'])
                ->name('download')
                ->middleware('throttle:30,1')
                ->where('id', '[0-9]+');
        });

        // ── Contact Center ─────────────────────────────────────────
        Route::get('/contact',   [ContactController::class, 'index'])->name('contact');
        Route::get('/emergency', [EmergencyController::class, 'index'])->name('emergency');

        // ── ITA & Open Data ────────────────────────────────────────
        Route::get('/ita',       [ItaController::class, 'index'])->name('ita');
        Route::get('/open-data', [OpenDataController::class, 'index'])->name('open-data');

        // ── Global Search ──────────────────────────────────────────
        Route::get('/search', [SearchController::class, 'index'])->name('search');

        // ── AI Immigration Assistant ───────────────────────────────
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::post('/conversation', [AiAssistantController::class, 'startConversation'])->name('conversation');
            Route::post('/chat',         [AiAssistantController::class, 'chat'])->name('chat')
                 ->middleware('throttle:20,1');
            Route::get('/suggested/{persona}', [AiAssistantController::class, 'suggestedQuestions'])->name('suggested');
        });

        // ── Language Switcher ──────────────────────────────────────
        Route::get('/lang/{locale}', function (string $locale) {
            if (in_array($locale, ['th', 'en'])) {
                session(['locale' => $locale]);
            }
            return redirect()->back()->withInput();
        })->name('lang.switch')->where('locale', 'th|en');
    });
