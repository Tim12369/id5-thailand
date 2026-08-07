<?php

namespace App\Http\Controllers\Internal\Website;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages only the homepage hero's text (title/subtitle/CTA label) via the
 * same Setting key/value pattern already used for the hero background image
 * (see Internal\Settings\SiteSettingsController). The homepage's action
 * cards, persona row, and bottom CTA banners are deliberately NOT exposed
 * here -- they are fixed navigation into real functional routes (visa
 * services, TM30, smart guide, emergency), not freeform content, and making
 * them arbitrarily admin-editable risks producing broken/mismatched links
 * into those subsystems.
 */
class HomepageController extends Controller
{
    private const KEYS = [
        'hero_title_th' => 'home.hero_title_th',
        'hero_title_en' => 'home.hero_title_en',
        'hero_subtitle_th' => 'home.hero_subtitle_th',
        'hero_subtitle_en' => 'home.hero_subtitle_en',
        'hero_cta_th' => 'home.hero_cta_th',
        'hero_cta_en' => 'home.hero_cta_en',
    ];

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.view'), 403);

        $values = [];
        foreach (self::KEYS as $field => $settingKey) {
            $values[$field] = Setting::get($settingKey, '');
        }

        return view('internal.website.homepage', [
            'values' => $values,
            'defaults' => [
                'hero_title_th' => __('home.hero_title', [], 'th'),
                'hero_title_en' => __('home.hero_title', [], 'en'),
                'hero_subtitle_th' => __('home.hero_subtitle', [], 'th'),
                'hero_subtitle_en' => __('home.hero_subtitle', [], 'en'),
                'hero_cta_th' => __('home.hero_cta', [], 'th'),
                'hero_cta_en' => __('home.hero_cta', [], 'en'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $request->validate([
            'hero_title_th' => ['nullable', 'string', 'max:500'],
            'hero_title_en' => ['nullable', 'string', 'max:500'],
            'hero_subtitle_th' => ['nullable', 'string', 'max:1000'],
            'hero_subtitle_en' => ['nullable', 'string', 'max:1000'],
            'hero_cta_th' => ['nullable', 'string', 'max:100'],
            'hero_cta_en' => ['nullable', 'string', 'max:100'],
        ]);

        foreach (self::KEYS as $field => $settingKey) {
            Setting::set($settingKey, $data[$field] ?? '');
        }

        return back()->with('success', 'Homepage content updated.');
    }
}
