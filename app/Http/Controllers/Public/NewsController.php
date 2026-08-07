<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Content\ArticleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    private const TAB_TYPE_MAP = [
        'news' => 'news',
        'announcements' => 'announcement',
        'alerts' => 'alert',
    ];

    public function __construct(private ArticleService $articleService) {}

    /**
     * A single hub page showing Latest News, Announcements, and Alerts as
     * tabs -- the same three content types shown on the homepage, but as
     * full paginated listings rather than 3-item previews.
     */
    public function index(Request $request): View
    {
        $requestedTab = $request->query('tab', 'news');
        $tab = array_key_exists($requestedTab, self::TAB_TYPE_MAP) ? $requestedTab : 'news';

        $articles = $this->articleService->getPublicListing(type: self::TAB_TYPE_MAP[$tab]);

        return view('public.news.index', compact('articles', 'tab'));
    }

    public function show(string $slug): View
    {
        $article = $this->articleService->getBySlug($slug);
        $related = $this->articleService->getRelated($article);

        return view('public.news.show', compact('article', 'related'));
    }
}
