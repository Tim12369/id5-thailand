<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Content\ArticleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function __construct(private ArticleService $articleService) {}

    public function index(Request $request): View
    {
        $articles   = $this->articleService->getPublicListing(
            type:         'kb_public',
            categorySlug: $request->query('category'),
            tagSlug:      $request->query('tag'),
        );
        $categories = $this->articleService->getCategoriesForType('kb_public');

        return view('public.knowledge.index', compact('articles', 'categories'));
    }

    public function show(string $slug): View
    {
        $article = $this->articleService->getBySlug($slug);
        $related = $this->articleService->getRelated($article);

        return view('public.knowledge.show', compact('article', 'related'));
    }
}
