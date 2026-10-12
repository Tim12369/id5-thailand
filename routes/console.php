<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
*/

/*
 * Reconcile the website's content with WordPress.
 *
 * Editors' changes already arrive within seconds through the webhook; this is
 * the safety net for anything that webhook missed — a delivery attempted while
 * the site was deploying, an edit made while the queue worker was stopped, or a
 * change applied directly in the WordPress database. It is safe to run at any
 * time: every write is keyed on the WordPress post ID, so a reconcile either
 * changes nothing or repairs a difference.
 */
Schedule::command('cms:pull')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->skip(fn () => ! config('cms.enabled'))
    ->onFailure(function () {
        logger()->channel('cms')->error('Scheduled CMS reconcile failed.');
    });
