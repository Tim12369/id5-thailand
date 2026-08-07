<?php

namespace App\Http\Controllers\Internal\Website;

use App\Http\Controllers\Controller;
use App\Models\HomeDivisionCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Lets admins edit the photo/title/description of all 6 homepage "Explore
 * ID5 Services" cards -- the 4 real divisions (Administrative Affairs,
 * Foreigner Services, Investigation, Immigration Technology) plus the "Who
 * Are You?" and "Where Are You?" feature cards. A fixed set, like Contact
 * Information's Stations -- this only edits existing rows.
 */
class DivisionCardController extends Controller
{
    private const PHOTO_DIR = 'home-divisions';

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('website.view'), 403);

        return view('internal.website.divisions.index', [
            'cards' => HomeDivisionCard::orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Request $request, HomeDivisionCard $homeDivisionCard): View
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        return view('internal.website.divisions.edit', ['card' => $homeDivisionCard]);
    }

    public function update(Request $request, HomeDivisionCard $homeDivisionCard): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('website.manage'), 403);

        $data = $request->validate([
            'title_th' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'desc_th' => ['nullable', 'string', 'max:255'],
            'desc_en' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('photo')) {
            if ($homeDivisionCard->photo_path && Storage::disk('public')->exists($homeDivisionCard->photo_path)) {
                Storage::disk('public')->delete($homeDivisionCard->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store(self::PHOTO_DIR, 'public');
        }

        $homeDivisionCard->update($data);

        return redirect()->route('internal.website-divisions.index')->with('success', 'Division card updated.');
    }
}
