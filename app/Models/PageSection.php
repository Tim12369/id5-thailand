<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Content an editor built from blocks in WordPress: a section placed on one of
 * the website's pages, or a page of its own (see the migration).
 */
class PageSection extends Model
{
    public const SECTION = 'section';

    public const PAGE = 'page';

    protected $fillable = [
        'kind', 'page', 'position', 'slug', 'title_th', 'title_en', 'excerpt',
        'body_html', 'language', 'background', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published'  => 'boolean',
            'cms_synced_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /** Shown to a visitor reading in the current language. */
    public function scopeForLocale(Builder $q, ?string $locale = null): Builder
    {
        $locale = ($locale ?? App::getLocale()) === 'en' ? 'en' : 'th';

        return $q->whereIn('language', ['both', $locale]);
    }

    public function getLocalizedTitle(): string
    {
        return (App::getLocale() === 'en' && filled($this->title_en)) ? $this->title_en : (string) $this->title_th;
    }

    /** The public address of a page (kind "page"). */
    public function url(): string
    {
        return route('public.page', $this->slug ?: $this->wp_post_id);
    }
}
