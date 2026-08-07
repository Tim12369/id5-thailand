<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Content\ArticleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private ArticleService $articleService) {}

    public function index(Request $request): View
    {
        $articles = $this->articleService->getPublicListing(
            type:         'announcement',
            categorySlug: $request->query('category'),
        );

        return view('public.announcements.index', compact('articles'));
    }

    public function show(string $slug): View
    {
        $article = $this->articleService->getBySlug($slug);

        return view('public.announcements.show', compact('article'));
    }
}
