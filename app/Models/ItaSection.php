<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class ItaSection extends Model
{
    protected $fillable = [
        'section_number', 'title_th', 'title_en',
        'description_th', 'description_en',
        'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(ItaCard::class, 'section_id')->orderBy('sort_order');
    }

    public function getLocalizedTitle(): string
    {
        return match (App::getLocale()) {
            'en' => $this->title_en ?: $this->title_th,
            default => $this->title_th,
        };
    }

    public function getLocalizedDescription(): ?string
    {
        return match (App::getLocale()) {
            'en' => $this->description_en ?: $this->description_th,
            default => $this->description_th,
        };
    }
}
