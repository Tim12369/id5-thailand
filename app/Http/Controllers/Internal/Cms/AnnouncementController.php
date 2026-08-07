<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Requests\Internal\Cms\AnnouncementRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;

class AnnouncementController extends ArticleCrudController
{
    protected string $type = 'announcement';
    protected string $viewPrefix = 'internal.cms.announcements';
    protected string $routePrefix = 'internal.cms.announcements';

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        return $this->persistCreate($request, $request->validated());
    }

    public function update(AnnouncementRequest $request, Article $article): RedirectResponse
    {
        return $this->persistUpdate($request, $article, $request->validated());
    }
}
