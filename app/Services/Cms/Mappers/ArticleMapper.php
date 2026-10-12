<?php

namespace App\Services\Cms\Mappers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * News, announcements, alerts and knowledge-base articles.
 *
 * All four are one WordPress post type each but one `articles` row shape, so
 * they share a mapper and are told apart by `article_type` in the payload.
 */
class ArticleMapper extends Mapper
{
    /** WordPress post type => `articles.type`. */
    private const TYPES = [
        'post'             => 'news',
        'id5_announcement' => 'announcement',
        'id5_alert'        => 'alert',
        'id5_knowledge'    => 'kb_public',
    ];

    public function knownPostIds(string $postType): array
    {
        $type = self::TYPES[$postType] ?? null;

        return $type
            ? Article::where('type', $type)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all()
            : [];
    }

    public function upsert(array $payload): ?Model
    {
        $type = $payload['article_type'] ?? null;

        if (! $type) {
            return null;
        }

        $article = Article::withTrashed()->firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        if ($article->trashed()) {
            $article->restore();
        }

        $article->fill([
            'type'            => $type,
            'title_th'        => $payload['title'] ?: '(ไม่มีหัวข้อ)',
            'title_en'        => $this->field($payload, 'title_en'),
            'slug'            => $this->uniqueSlug($payload, $article),
            'excerpt_th'      => $payload['excerpt'] ?: null,
            'excerpt_en'      => $this->field($payload, 'excerpt_en'),
            'body_th'         => $this->html($payload, $payload['content'] ?? ''),
            'body_en'         => $this->html($payload, $this->field($payload, 'body_en')),
            'status'          => $payload['status'],
            'is_public'       => true,
            'is_pinned'       => $this->bool($payload, 'is_pinned'),
            'alert_level'     => $type === 'alert' ? ($this->field($payload, 'alert_level') ?? 'info') : null,
            'published_at'    => $payload['published_at'],
            'expired_at'      => $this->field($payload, 'expired_at'),
            'seo_title'       => $this->field($payload, 'seo_title'),
            'seo_description' => $this->field($payload, 'seo_description'),
            'category_id'     => $this->resolveCategory(
                $this->term($payload, $type === 'news' ? 'category' : 'id5_content_category'),
                $type,
            ),
        ]);

        $article->thumbnail = $this->media->mirror($payload['image'] ?? null, $article->thumbnail);

        // view_count is a runtime counter owned by this application — never
        // written here, so syncing does not reset the public read tally.
        $article->wp_post_id    = $payload['wp_post_id'];
        $article->cms_synced_at = now();
        $article->save();

        $this->syncTags($article, $payload);

        return $article;
    }

    /**
     * A permanently deleted post becomes a soft-deleted article: the public site
     * stops showing it immediately, and the row is still recoverable.
     */
    public function delete(int $wpPostId): void
    {
        Article::where('wp_post_id', $wpPostId)->get()->each->delete();
    }

    /**
     * Slugs are public URLs, so keep WordPress's exactly unless it collides
     * with a different article.
     */
    private function uniqueSlug(array $payload, Article $article): string
    {
        $slug = $this->cleanSlug($payload['slug'] ?? null);

        if ($slug === null) {
            // No usable slug from WordPress (a Thai title, or the post is in the
            // trash). An article that already has an address keeps it — a
            // published URL must never change underneath its readers.
            if ($article->exists && filled($article->slug)) {
                return $article->slug;
            }

            // Otherwise build one the way the old admin did: from the English
            // title when there is one, else from the type and post number.
            $slug = Str::slug((string) $this->field($payload, 'title_en'))
                ?: Str::slug(($payload['article_type'] ?? 'content').'-'.$payload['wp_post_id']);
        }

        $taken = Article::withTrashed()
            ->where('slug', $slug)
            ->when($article->exists, fn ($q) => $q->where('id', '!=', $article->id))
            ->exists();

        return $taken ? $slug.'-'.$payload['wp_post_id'] : $slug;
    }

    private function syncTags(Article $article, array $payload): void
    {
        $terms = $this->terms($payload, 'post_tag');

        if (! $terms) {
            $article->tags()->sync([]);

            return;
        }

        $ids = [];

        foreach ($terms as $term) {
            $tag = Tag::firstOrNew(['slug' => $term['slug']]);
            $tag->name       = $term['name'];
            $tag->wp_term_id = $term['term_id'];
            $tag->save();

            $ids[] = $tag->id;
        }

        $article->tags()->sync($ids);
    }
}
