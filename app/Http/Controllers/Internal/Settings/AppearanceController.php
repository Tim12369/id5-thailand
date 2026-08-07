<?php

namespace App\Http\Controllers\Internal\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stores a brand accent color, persisted for future use. The public site's
 * Tailwind theme currently uses compile-time color tokens (see
 * resources/css/app.css @theme block), so this value isn't wired into the
 * live pages yet -- rethreading every hardcoded `bg-blue-700`-style class to
 * read a runtime CSS variable is a larger, separate design-system change.
 * This screen exists so that work has a real, persisted setting to build on.
 */
class AppearanceController extends Controller
{
    private const ACCENT_COLOR_KEY = 'site.accent_color';
    private const DEFAULT_ACCENT_COLOR = '#1d4ed8';

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);

        return view('internal.settings.appearance', [
            'accentColor' => Setting::get(self::ACCENT_COLOR_KEY, self::DEFAULT_ACCENT_COLOR),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate([
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Setting::set(self::ACCENT_COLOR_KEY, $data['accent_color']);

        return back()->with('success', 'Appearance settings saved.');
    }
}
