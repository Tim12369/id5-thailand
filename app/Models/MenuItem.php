<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class MenuItem extends Model
{
    public const LOCATION_HEADER = 'header';
    public const LOCATION_FOOTER = 'footer';

    protected $fillable = ['location', 'label_th', 'label_en', 'url', 'parent_id', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeLocation(Builder $q, string $location): Builder
    {
        return $q->where('location', $location)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('is_visible', true);
    }

    public function getLocalizedLabel(): string
    {
        return match (App::getLocale()) {
            'en' => $this->label_en ?: $this->label_th,
            default => $this->label_th,
        };
    }
}
