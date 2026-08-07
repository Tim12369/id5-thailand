<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Requests\Internal\Cms\KnowledgeBaseRequest;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KnowledgeBaseController extends ArticleCrudController
{
    protected string $type = 'kb_public';
    protected string $viewPrefix = 'internal.cms.knowledge-base';
    protected string $routePrefix = 'internal.cms.knowledge-base';

    public function index(): View
    {
        abort_unless(request()->user()->hasPermission('cms.view'), 403);

        return parent::index();
    }

    public function create(): View
    {
        abort_unless(request()->user()->hasPermission('cms.manage'), 403);

        return parent::create();
    }

    public function edit(Article $article): View
    {
        abort_unless(request()->user()->hasPermission('cms.manage'), 403);

        return parent::edit($article);
    }

    public function store(KnowledgeBaseRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        return $this->persistCreate($request, $request->validated());
    }

    public function update(KnowledgeBaseRequest $request, Article $article): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cms.manage'), 403);

        return $this->persistUpdate($request, $article, $request->validated());
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('cms.manage'), 403);

        return parent::destroy($article);
    }

    public function togglePublish(Article $article): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('cms.manage'), 403);

        return parent::togglePublish($article);
    }

    protected function formViewData(?Article $article): array
    {
        return [
            'article' => $article,
            'categories' => Category::ofType('kb_public')->active()->orderBy('sort_order')->get(),
        ];
    }
}
