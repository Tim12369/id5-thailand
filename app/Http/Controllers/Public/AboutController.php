<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OrgChartSection;
use App\Models\PublicForm;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $sections = OrgChartSection::shownOnAboutPage()->ordered()->with('tiers.members')->get();
        $documents = PublicForm::active()->shownOnAboutPage()->orderBy('sort_order')->get();

        return view('public.about.index', compact('sections', 'documents'));
    }

    public function ecosystem(): View
    {
        return view('public.about.ecosystem');
    }
}
