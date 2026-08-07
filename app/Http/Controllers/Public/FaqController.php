<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Content\FaqService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(private FaqService $faqService) {}

    public function index(Request $request): View
    {
        $search  = $request->query('q');
        $grouped = $this->faqService->getGroupedPublicFaqs($search);

        return view('public.faq.index', compact('grouped', 'search'));
    }
}
