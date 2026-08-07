<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Content\ArticleService;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private ArticleService $articleService) {}

    public function index(): View
    {
        $alerts = $this->articleService->getPublicListing('alert', perPage: 20);

        return view('public.alerts.index', compact('alerts'));
    }
}
