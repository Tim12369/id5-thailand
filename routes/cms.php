<?php

use App\Http\Controllers\Cms\EditModeController;
use App\Http\Controllers\Cms\TextCatalogueController;
use App\Http\Controllers\Cms\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CMS integration
|--------------------------------------------------------------------------
|
| The single endpoint the WordPress editing install calls when content
| changes. It is not part of the public site and is not browsable: it accepts
| only a POST carrying a valid HMAC signature.
|
| No `web` middleware: there is no session, no cookie and no CSRF token here —
| authentication is the signature, which is stronger than either for a
| server-to-server call and is the reason this route is also excluded from CSRF
| verification in bootstrap/app.php.
|
*/

Route::post('/cms/webhook', WebhookController::class)
    ->middleware(['cms.webhook', 'throttle:600,1'])
    ->name('cms.webhook');

/*
 * The list of editable wording (menu labels, headings, buttons…) with its
 * built-in text, for the WordPress editor. Signed the same way as the webhook.
 */
Route::get('/cms/texts', TextCatalogueController::class)
    ->middleware(['cms.webhook', 'throttle:120,1'])
    ->name('cms.texts');

/*
 * WordPress does not live on this site, but administrators who know WordPress
 * type these addresses out of habit. Send them to where it does live instead of
 * answering "404 Not Found". WordPress still asks for its own login.
 */
Route::get('/wp-admin/{any?}', [EditModeController::class, 'wordpress'])->where('any', '.*');
Route::get('/wp-login.php', [EditModeController::class, 'wordpress']);
