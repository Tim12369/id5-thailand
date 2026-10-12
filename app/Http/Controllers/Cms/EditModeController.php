<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Support\CmsEdit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The doors between the public website and the WordPress editor.
 *
 * None of these grant anything: they only send a browser to WordPress, which
 * asks for its own login before showing an editing screen.
 */
class EditModeController extends Controller
{
    /**
     * `/edit` — a memorable address for "I want to change the website".
     * Opens the WordPress site editor on whichever page the editor came from.
     */
    public function enter(Request $request): RedirectResponse
    {
        abort_unless(config('cms.enabled') && filled(config('cms.base_url')), 404);

        $path = $request->query('path', '/');
        $path = (is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//')) ? $path : '/';

        return redirect()->away(CmsEdit::editorUrl($path));
    }

    /**
     * `/edit/exit` — put the pencils away and see the site as a visitor does.
     */
    public function leave(Request $request): RedirectResponse
    {
        CmsEdit::leave($request);

        return redirect()->route('public.home');
    }

    /**
     * `/wp-admin` and `/wp-login.php` on the public website.
     *
     * Administrators who know WordPress reach for these addresses out of habit.
     * WordPress does not live on this site, so rather than answer with a bare
     * "404 Not Found", point them at where it does live.
     */
    public function wordpress(): RedirectResponse
    {
        abort_unless(config('cms.enabled') && filled(config('cms.base_url')), 404);

        return redirect()->away(CmsEdit::wordpressUrl().'/wp-admin/');
    }
}
