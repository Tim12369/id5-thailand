<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class ItaCard extends Model
{
    protected $fillable = [
        'section_id', 'card_number', 'title_th', 'title_en',
        'content_th', 'content_en', 'icon', 'color',
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
