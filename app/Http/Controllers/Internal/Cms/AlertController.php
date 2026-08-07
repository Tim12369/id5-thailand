<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Requests\Internal\Cms\AlertRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;

class AlertController extends ArticleCrudController
{
    protected string $type = 'alert';
    protected string $viewPrefix = 'internal.cms.alerts';
    protected string $routePrefix = 'internal.cms.alerts';

    public function store(AlertRequest $request): RedirectResponse
    {
        return $this->persistCreate($request, $request->validated());
    }

    public function update(AlertRequest $request, Article $article): RedirectResponse
    {
        return $this->persistUpdate($request, $article, $request->validated());
    }

    /**
     * Alerts use a single "Short Message" field (excerpt_*), but the
     * articles table requires body_th — mirror the short message into body.
     */
    protected function prepareData(array $data, ?Article $article): array
    {
        $data['body_th'] = $data['excerpt_th'];
        $data['body_en'] = $data['excerpt_en'] ?? null;

        return $data;
    }
}
