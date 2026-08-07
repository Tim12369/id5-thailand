<?php

namespace App\Http\Controllers\Internal\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * General site identity settings (name, emergency hotline). Kept separate
 * from SiteSettingsController (which only manages the homepage hero image)
 * and from Website\HomepageController (hero text) -- this is the smallest,
 * most site-wide layer: the values shown across every page's header/footer.
 */
class GeneralSettingsController extends Controller
{
    private const SITE_NAME_KEY = 'general.site_name';
    private const EMERGENCY_PHONE_KEY = 'general.emergency_phone';

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);

        return view('internal.settings.general', [
            'siteName' => Setting::get(self::SITE_NAME_KEY, 'ID5 @ Thailand 5.0'),
            'emergencyPhone' => Setting::get(self::EMERGENCY_PHONE_KEY, '1178'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'emergency_phone' => ['required', 'string', 'max:50'],
        ]);

        Setting::set(self::SITE_NAME_KEY, $data['site_name']);
        Setting::set(self::EMERGENCY_PHONE_KEY, $data['emergency_phone']);

        return back()->with('success', 'Website settings updated.');
    }
}
