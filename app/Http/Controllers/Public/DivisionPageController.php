<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OrgChartSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shared controller behind all 4 division pages (Technology, Administrative
 * Affairs, Foreigner Services, Investigation) reachable from the homepage
 * "Explore ID5 Services" grid. Each route binds its own `orgSlug`/`lang`/
 * `theme` via ->defaults() in routes/web.php; this class has no per-division
 * logic of its own, so there's one controller and one view instead of four
 * near-identical copies.
 *
 * All 4 are backed by the same OrgChartSection/Tier/Member data as the About
 * Us page's leadership charts -- they just render on their own page instead
 * of as an About Us tab (see show_on_about_page on OrgChartSection). That
 * data is maintained in WordPress under บุคลากร (People) and synced in by
 * App\Services\Cms.
 */
class DivisionPageController extends Controller
{
    /**
     * One visual identity per division -- each echoes that division's own
     * gradient on the homepage card, so clicking through feels continuous,
     * while staying visibly distinct from About Us's red/gold and navy/gold
     * treatments and from each other.
     */
    private const THEMES = [
        'technology_division' => [
            'panelBg' => 'bg-[radial-gradient(ellipse_at_top,#312e81,#0f0a2e_55%,#000_100%)]',
            'border' => 'border-cyan-400/40',
            'patternColor' => 'rgba(34,211,238,.12)',
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(34,211,238,0.22),transparent_70%)]',
            'corner' => 'text-cyan-300/50',
            'banner' => 'from-cyan-500 via-violet-400 to-cyan-500 text-slate-950 border-cyan-200/70 ring-cyan-100/30',
            'tierBanner' => 'from-cyan-600 via-violet-500 to-cyan-600 ring-cyan-200/60',
            'cardOuter' => 'from-cyan-300 via-cyan-500 to-violet-700 shadow-[0_0_18px_-2px_rgba(34,211,238,0.35)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,#1e1b4b,#0a0a1f_100%)]',
            'nameRibbon' => 'from-slate-900 via-indigo-950 to-slate-900 text-cyan-200 border-cyan-400/20',
            'icon' => 'ti-cpu',
        ],
        'administration_division' => [
            'panelBg' => 'bg-[radial-gradient(ellipse_at_top,#1e293b,#0f172a_55%,#000_100%)]',
            'border' => 'border-slate-400/40',
            'patternColor' => 'rgba(148,163,184,.14)',
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(148,163,184,0.20),transparent_70%)]',
            'corner' => 'text-slate-300/50',
            'banner' => 'from-slate-300 via-white to-slate-300 text-slate-900 border-slate-200/70 ring-slate-100/30',
            'tierBanner' => 'from-slate-600 via-slate-400 to-slate-600 ring-slate-200/60',
            'cardOuter' => 'from-slate-300 via-slate-500 to-slate-700 shadow-[0_0_18px_-2px_rgba(148,163,184,0.35)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,#1e293b,#020617_100%)]',
            'nameRibbon' => 'from-slate-900 via-slate-800 to-slate-900 text-slate-200 border-slate-400/20',
            'icon' => 'ti-building-bank',
        ],
        'foreigner_division' => [
            'panelBg' => 'bg-[radial-gradient(ellipse_at_top,#134e4a,#052e2b_55%,#000_100%)]',
            'border' => 'border-teal-400/40',
            'patternColor' => 'rgba(45,212,191,.14)',
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(45,212,191,0.20),transparent_70%)]',
            'corner' => 'text-teal-300/50',
            'banner' => 'from-teal-400 via-emerald-300 to-teal-400 text-teal-950 border-teal-200/70 ring-teal-100/30',
            'tierBanner' => 'from-teal-600 via-emerald-500 to-teal-600 ring-teal-200/60',
            'cardOuter' => 'from-teal-300 via-emerald-500 to-teal-800 shadow-[0_0_18px_-2px_rgba(45,212,191,0.35)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,#134e4a,#041a17_100%)]',
            'nameRibbon' => 'from-slate-900 via-teal-950 to-slate-900 text-teal-200 border-teal-400/20',
            'icon' => 'ti-passport',
        ],
        'investigation_division' => [
            'panelBg' => 'bg-[radial-gradient(ellipse_at_top,#4c0519,#1c0510_55%,#000_100%)]',
            'border' => 'border-rose-400/40',
            'patternColor' => 'rgba(251,113,133,.12)',
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(251,113,133,0.18),transparent_70%)]',
            'corner' => 'text-rose-300/50',
            'banner' => 'from-rose-500 via-red-400 to-rose-500 text-rose-950 border-rose-200/70 ring-rose-100/30',
            'tierBanner' => 'from-rose-700 via-red-500 to-rose-700 ring-rose-200/60',
            'cardOuter' => 'from-rose-300 via-red-500 to-rose-900 shadow-[0_0_18px_-2px_rgba(251,113,133,0.35)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,#4c0519,#190309_100%)]',
            'nameRibbon' => 'from-slate-900 via-rose-950 to-slate-900 text-rose-200 border-rose-400/20',
            'icon' => 'ti-fingerprint',
        ],
    ];

    public function index(Request $request): View
    {
        $orgSlug = $request->route('orgSlug');
        $lang = $request->route('lang');

        $section = OrgChartSection::where('slug', $orgSlug)->with('tiers.members')->first();
        $theme = self::THEMES[$orgSlug];

        return view('public.division-page.index', [
            'section' => $section,
            'theme' => $theme,
            'lang' => $lang,
        ]);
    }
}
