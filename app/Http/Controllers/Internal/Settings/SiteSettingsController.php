<?php

namespace App\Http\Controllers\Internal\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    private const HERO_IMAGE_KEY = 'site.hero_image';
    private const HERO_IMAGE_DIR = 'site/hero';
    private const DEFAULT_HERO_IMAGE_URL = '/images/hero-temple.jpg';

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);

        $heroImagePath = Setting::get(self::HERO_IMAGE_KEY);

        return view('internal.settings.edit', [
            'heroImageUrl' => $heroImagePath
                ? asset('storage/' . $heroImagePath)
                : asset(self::DEFAULT_HERO_IMAGE_URL),
            'hasCustomHeroImage' => (bool) $heroImagePath,
        ]);
    }

    public function updateHeroImage(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $request->validate([
            'hero_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $this->deleteExistingHeroImage();

        $path = $request->file('hero_image')->store(self::HERO_IMAGE_DIR, 'public');

        Setting::set(self::HERO_IMAGE_KEY, $path);

        return redirect()->route('internal.settings.edit')->with('success', 'Homepage hero image updated.');
    }

    public function destroyHeroImage(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $this->deleteExistingHeroImage();

        Setting::set(self::HERO_IMAGE_KEY, '');

        return redirect()->route('internal.settings.edit')->with('success', 'Homepage hero image reset to default.');
    }

    private function deleteExistingHeroImage(): void
    {
        $existing = Setting::get(self::HERO_IMAGE_KEY);

        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }
    }
}
