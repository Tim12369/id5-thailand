<?php

namespace App\Services\Content;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ArticleService
{
    /**
     * Homepage: pinned + latest news, alerts, announcements.
     */
    public function getHomepageContent(): array
    {
        return [
            'pinned_news' => Article::published()->public()
                ->ofType('news')->pinned()
                ->with('category')
                ->orderByDesc('published_at')
                ->limit(1)
                ->get(),

            'latest_news' => Article::published()->public()
                ->ofType('news')
                ->with('category')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(),

            'announcements' => Article::published()->public()
                ->ofType('announcement')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(),

            'alerts' => Article::published()->public()
                ->ofType('alert')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Paginated listing for news / announcements / alerts / kb_public.
     */
    public function getPublicListing(
        string $type,
        ?string $categorySlug = null,
        ?string $tagSlug = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $q = Article::published()->public()
            ->ofType($type)
            ->with(['category', 'tags'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');

        if ($categorySlug) {
            $q->whereHas('category', fn($c) => $c->where('slug', $categorySlug));
        }

        if ($tagSlug) {
            $q->whereHas('tags', fn($t) => $t->where('slug', $tagSlug));
        }

        return $q->paginate($perPage)->withQueryString();
    }

    /**
     * Single article by slug — increments view count.
     */
    public function getBySlug(string $slug): Article
    {
        $article = Article::published()->public()
            ->where('slug', $slug)
            ->with(['category', 'tags', 'attachments', 'author'])
            ->firstOrFail();

        $article->incrementViewCount();

        return $article;
    }

    /**
     * Related articles of the same type / category.
     */
    public function getRelated(Article $article, int $limit = 4): Collection
    {
        return Article::published()->public()
            ->ofType($article->type)
            ->where('id', '!=', $article->id)
            ->when($article->category_id, function ($q) use ($article) {
                $q->where('category_id', $article->category_id);
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Categories available for a given article type.
     */
    public function getCategoriesForType(string $type): Collection
    {
        return Category::active()
            ->ofType($type)
            ->roots()
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }
}
