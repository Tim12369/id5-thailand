<?php

namespace App\Http\Controllers\Internal\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

abstract class ArticleCrudController extends Controller
{
    /**
     * The article type this controller manages (news|announcement|alert).
     */
    protected string $type;

    /**
     * View path prefix, e.g. "internal.cms.news".
     */
    protected string $viewPrefix;

    /**
     * Route name prefix, e.g. "internal.cms.news".
     */
    protected string $routePrefix;

    public function index(): View
    {
        $articles = Article::ofType($this->type)
            ->latest('created_at')
            ->paginate(15);

        return view("{$this->viewPrefix}.index", $this->indexViewData($articles));
    }

    public function create(): View
    {
        return view("{$this->viewPrefix}.create", $this->formViewData(null));
    }

    public function edit(Article $article): View
    {
        return view("{$this->viewPrefix}.edit", $this->formViewData($article));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', $this->label() . ' deleted successfully.');
    }

    public function togglePublish(Article $article): RedirectResponse
    {
        if ($article->status === 'published') {
            $article->status = 'draft';
        } else {
            $article->status = 'published';
            $article->published_at = $article->published_at ?? now();
        }

        $article->save();

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', $this->label() . ' ' . ($article->status === 'published' ? 'published' : 'unpublished') . '.');
    }

    /**
     * Create a new Article of $this->type from validated request data.
     */
    protected function persistCreate(Request $request, array $data): RedirectResponse
    {
        $data['thumbnail'] = $this->handleThumbnailUpload($request, null);
        $data = $this->prepareData($data, null);
        $data['type'] = $this->type;
        $data['author_id'] = auth()->id();
        $data['is_public'] = true;
        $data['status'] = $data['status'] ?? 'draft';
        $data['slug'] = $this->generateSlug($data['title_en'] ?? null, $data['title_th']);

        Article::create($data);

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', $this->label() . ' created successfully.');
    }

    /**
     * Update an existing Article from validated request data.
     */
    protected function persistUpdate(Request $request, Article $article, array $data): RedirectResponse
    {
        $data['thumbnail'] = $this->handleThumbnailUpload($request, $article);
        $data = $this->prepareData($data, $article);

        $article->update($data);

        return redirect()->route("{$this->routePrefix}.index")
            ->with('success', $this->label() . ' updated successfully.');
    }

    /**
     * Hook for subclasses to enrich/transform validated data before save.
     */
    protected function prepareData(array $data, ?Article $article): array
    {
        return $data;
    }

    /**
     * Extra data passed to the index view.
     */
    protected function indexViewData($articles): array
    {
        return ['articles' => $articles];
    }

    /**
     * Extra data passed to the create/edit views.
     */
    protected function formViewData(?Article $article): array
    {
        return ['article' => $article];
    }

    protected function label(): string
    {
        return ucfirst($this->type);
    }

    protected function generateSlug(?string $titleEn, string $titleTh): string
    {
        $base = Str::slug($titleEn ?: $titleTh) ?: Str::slug($this->type . '-' . Str::random(6));
        $slug = $base;
        $i = 2;

        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function handleThumbnailUpload(Request $request, ?Article $article): ?string
    {
        if (! $request->hasFile('thumbnail')) {
            return $article?->thumbnail;
        }

        if ($article?->thumbnail) {
            Storage::disk('public')->delete($article->thumbnail);
        }

        return $request->file('thumbnail')->store("articles/{$this->type}", 'public');
    }
}
