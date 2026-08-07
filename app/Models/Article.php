<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'category_id', 'author_id',
        'title_th', 'title_en', 'title_zh',
        'slug',
        'excerpt_th', 'excerpt_en', 'excerpt_zh',
        'body_th', 'body_en', 'body_zh',
        'thumbnail', 'status', 'is_public', 'is_pinned',
        'alert_level', 'priority', 'published_at', 'expired_at',
        'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_public'    => 'boolean',
            'is_pinned'    => 'boolean',
            'published_at' => 'datetime',
            'expired_at'   => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ArticleAttachment::class)->orderBy('sort_order');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
                 ->where(function ($q) {
                     $q->whereNull('published_at')
                       ->orWhere('published_at', '<=', now());
                 })
                 ->where(function ($q) {
                     $q->whereNull('expired_at')
                       ->orWhere('expired_at', '>', now());
                 });
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }

    public function scopeOfType(Builder $q, string|array $type): Builder
    {
        return is_array($type)
            ? $q->whereIn('type', $type)
            : $q->where('type', $type);
    }

    public function scopePinned(Builder $q): Builder
    {
        return $q->where('is_pinned', true);
    }

    public function scopeForLocale(Builder $q): Builder
    {
        return $q;
    }

    // ─── Localization helpers ─────────────────────────────────────

    public function getLocalizedTitle(): string
    {
        $locale = App::getLocale();
        return match ($locale) {
            'en' => $this->title_en ?: $this->title_th,
            'zh' => $this->title_zh ?: $this->title_th,
            default => $this->title_th,
        };
    }

    public function getLocalizedExcerpt(): ?string
    {
        $locale = App::getLocale();
        return match ($locale) {
            'en' => $this->excerpt_en ?: $this->excerpt_th,
            'zh' => $this->excerpt_zh ?: $this->excerpt_th,
            default => $this->excerpt_th,
        };
    }

    public function getLocalizedBody(): string
    {
        $locale = App::getLocale();
        return match ($locale) {
            'en' => $this->body_en ?: $this->body_th,
            'zh' => $this->body_zh ?: $this->body_th,
            default => $this->body_th,
        };
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail
            ? asset('storage/' . $this->thumbnail)
            : null;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
