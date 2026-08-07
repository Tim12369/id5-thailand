<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuideTopic extends Model
{
    protected $fillable = [
        'topic', 'icon', 'color', 'search_keywords',
        'deadline_days', 'is_urgent', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GuideDocument::class)->orderBy('sort_order');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(GuideStep::class)->orderBy('step_number');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(GuideLocation::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForTopic(Builder $q, string $topic): Builder
    {
        return $q->where('topic', $topic);
    }
}
