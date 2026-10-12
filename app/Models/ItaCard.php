<?php

namespace App\Models;

use App\Support\ItaIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class ItaCard extends Model
{
    protected $fillable = [
        'section_id', 'card_number', 'title_th', 'title_en',
        'content_th', 'content_en', 'icon', 'icon_image', 'color',
        'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ItaSection::class, 'section_id');
    }

    /** The picture an editor uploaded for this topic, if any. */
    public function getIconImageUrlAttribute(): ?string
    {
        return $this->icon_image ? asset('storage/'.$this->icon_image) : null;
    }

    /**
     * The icon to draw when there is no picture: the one the editor named, or
     * one chosen from what the topic is about (see App\Support\ItaIcon).
     */
    public function getDisplayIconAttribute(): string
    {
        return ItaIcon::normalise($this->icon) ?? ItaIcon::suggest($this->title_th, $this->title_en);
    }

    public function getLocalizedTitle(): string
    {
        return match (App::getLocale()) {
            'en' => $this->title_en ?: $this->title_th,
            default => $this->title_th,
        };
    }

    public function getLocalizedContent(): string
    {
        return match (App::getLocale()) {
            'en' => $this->content_en ?: $this->content_th,
            default => $this->content_th,
        };
    }
}
