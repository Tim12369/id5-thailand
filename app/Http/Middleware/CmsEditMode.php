<?php

namespace App\Http\Middleware;

use App\Support\CmsEdit;
use App\Support\CmsText;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides, for each public request, whether the visitor is a website editor in
 * "edit mode" (see App\Support\CmsEdit).
 *
 * For everyone else this does nothing at all. For an editor it also marks the
 * response as uncacheable — a page with pencils on it must never be stored and
 * served to a visitor — and names the WordPress install as the only other site
 * allowed to show this one inside a frame.
 */
class CmsEditMode
{
    public function handle(Request $request, Closure $next): Response
    {
        CmsEdit::boot($request);

        // For an editor, every line of wording printed on the page carries an
        // invisible tag saying which line it is, so a click on a heading or a
        // button can open the right form. Only pages get them: the assistant's
        // JSON answers are stored and sent onwards, and must stay exact.
        CmsText::setTagging(CmsEdit::active() && ! $request->expectsJson() && ! $request->routeIs('public.ai.*'));

        try {
            $response = $next($request);
        } finally {
            CmsText::setTagging(false);
        }

        if (CmsEdit::active()) {
            $response->headers->set('Cache-Control', 'no-store, private');

            $origin = $this->origin(CmsEdit::wordpressUrl());

            if ($origin) {
                $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' {$origin}");
            }
        }

        return $response;
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
