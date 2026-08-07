<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgChartSection extends Model
{
    protected $fillable = ['slug', 'name_th', 'name_en', 'theme', 'sort_order', 'show_on_about_page'];

    public const THEMES = ['red_gold', 'navy', 'navy_gold'];

    protected function casts(): array
    {
        return ['show_on_about_page' => 'boolean'];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(OrgChartTier::class, 'section_id')->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order');
    }

    public function scopeShownOnAboutPage(Builder $q): Builder
    {
        return $q->where('show_on_about_page', true);
    }
}
