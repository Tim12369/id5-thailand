<?php

namespace App\Http\Controllers\Internal\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only report of the locales the public site actually supports (see
 * App\Http\Middleware\SetLocale::$supported, the single source of truth).
 * Adding a new locale means adding lang files + registering it there, which
 * is a code change, not admin-editable data -- this screen exists so admins
 * can see current language coverage without needing to ask a developer.
 */
class LanguageController extends Controller
{
    private const LOCALES = [
        'th' => ['label' => 'Thai (ภาษาไทย)', 'is_default' => true],
        'en' => ['label' => 'English', 'is_default' => false],
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('settings.view'), 403);

        $langPath = lang_path();
        $coverage = [];

        foreach (self::LOCALES as $code => $meta) {
            $files = glob("{$langPath}/{$code}/*.php") ?: [];

            $coverage[$code] = [
                ...$meta,
                'file_count' => count($files),
            ];
        }

        return view('internal.settings.languages', ['coverage' => $coverage]);
    }
}
