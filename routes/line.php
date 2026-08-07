<?php

use App\Http\Controllers\Line\LineWebhookController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════
// MODULE 2: LINE OA — machine-to-machine webhook endpoint
// Stateless: no session, no CSRF (excluded in bootstrap/app.php),
// signature-verified instead.
// ═══════════════════════════════════════════════════════════════

Route::post('/line/webhook', [LineWebhookController::class, 'handle'])
    ->middleware(['line.signature'])
    ->name('line.webhook');
