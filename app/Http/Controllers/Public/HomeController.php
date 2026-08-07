<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomeDivisionCard;
use App\Services\Content\ArticleService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function index(): View
    {
        $content = $this->articleService->getHomepageContent();

        // All 6 cards in "Explore ID5 Services" — photo/text are admin-editable
        // via Internal Portal > Website Management > Homepage Divisions; the
        // icon/color/link-target per card stays fixed in the view since it's
        // purely decorative/functional, not content. (The "Who Are You?" card
        // links to the guided assessment wizard — see /assessment.)
        $divisionCards = HomeDivisionCard::orderBy('sort_order')->get()->keyBy('key');

        return view('public.home.index', compact('content', 'divisionCards'));
    }
}
