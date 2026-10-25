<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\View\View;

/**
 * A page editors built from blocks in WordPress ("หน้าเว็บเพิ่มเติม").
 *
 * Found by its slug, or — when WordPress had no plain-letter slug for it, as
 * with a Thai title — by its WordPress number. Both are what WordPress itself
 * links to, including from the navigation menus.
 */
class CustomPageController extends Controller
{
    public function show(string $key): View
    {
        $page = PageSection::published()
            ->where('kind', PageSection::PAGE)
            ->where(fn ($q) => ctype_digit($key) ? $q->where('slug', $key)->orWhere('wp_post_id', (int) $key) : $q->where('slug', $key))
            ->firstOrFail();

        return view('public.custom-page.show', compact('page'));
    }
}
