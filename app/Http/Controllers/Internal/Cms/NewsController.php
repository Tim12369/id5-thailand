<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Requests\Internal\Cms\NewsRequest;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class NewsController extends ArticleCrudController
{
    protected string $type = 'news';
    protected string $viewPrefix = 'internal.cms.news';
    protected string $routePrefix = 'internal.cms.news';

    public function store(NewsRequest $request): RedirectResponse
    {
        return $this->persistCreate($request, $request->validated());
    }

    public function update(NewsRequest $request, Article $article): RedirectResponse
    {
        return $this->persistUpdate($request, $article, $request->validated());
    }

    protected function formViewData(?Article $article): array
    {
        return [
            'article' => $article,
            'categories' => Category::ofType('news')->active()->orderBy('sort_order')->get(),
        ];
    }
}
