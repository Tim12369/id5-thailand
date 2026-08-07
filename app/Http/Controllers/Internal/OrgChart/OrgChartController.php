<?php

namespace App\Http\Controllers\Internal\OrgChart;

use App\Http\Controllers\Controller;
use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrgChartController extends Controller
{
    private const PHOTO_DIR = 'org-chart/members';

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('org-chart.view'), 403);

        $sections = OrgChartSection::ordered()->with('tiers.members')->get();

        return view('internal.org-chart.index', [
            'sections' => $sections,
            'canManage' => $request->user()->hasPermission('org-chart.manage'),
        ]);
    }

    public function storeMember(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('org-chart.manage'), 403);

        $data = $request->validate([
            'tier_id' => ['required', 'exists:org_chart_tiers,id'],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'rank_th' => ['nullable', 'string', 'max:255'],
            'position_th' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store(self::PHOTO_DIR, 'public')
            : null;

        $nextOrder = OrgChartMember::where('tier_id', $data['tier_id'])->max('sort_order') + 1;

        OrgChartMember::create([
            'tier_id' => $data['tier_id'],
            'name_th' => $data['name_th'],
            'name_en' => $data['name_en'] ?? null,
            'rank_th' => $data['rank_th'] ?? null,
            'position_th' => $data['position_th'] ?? null,
            'photo_path' => $photoPath,
            'sort_order' => $nextOrder,
        ]);

        return back()->with('success', 'Member added.');
    }

    public function updateMember(Request $request, OrgChartMember $member): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('org-chart.manage'), 403);

        $data = $request->validate([
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'rank_th' => ['nullable', 'string', 'max:255'],
            'position_th' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $photoPath = $member->photo_path;

        if ($request->hasFile('photo')) {
            $this->deletePhoto($member);
            $photoPath = $request->file('photo')->store(self::PHOTO_DIR, 'public');
        }

        $member->update([
            'name_th' => $data['name_th'],
            'name_en' => $data['name_en'] ?? null,
            'rank_th' => $data['rank_th'] ?? null,
            'position_th' => $data['position_th'] ?? null,
            'photo_path' => $photoPath,
        ]);

        return back()->with('success', 'Member updated.');
    }

    public function destroyMember(Request $request, OrgChartMember $member): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('org-chart.manage'), 403);

        $this->deletePhoto($member);
        $member->delete();

        return back()->with('success', 'Member removed.');
    }

    private function deletePhoto(OrgChartMember $member): void
    {
        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }
    }
}
