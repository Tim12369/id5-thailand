<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class PublicForm extends Model
{
    protected $fillable = [
        'code', 'name_th', 'name_en', 'name_zh',
        'description_th', 'description_en', 'description_zh',
        'category', 'path', 'disk', 'version',
        'file_size_bytes', 'download_count', 'is_active', 'show_on_about_page', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_on_about_page' => 'boolean'];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeShownOnAboutPage(Builder $q): Builder
    {
        return $q->where('show_on_about_page', true);
    }

    public function scopeOfCategory(Builder $q, string $category): Builder
    {
        return $q->where('category', $category);
    }

    public function getLocalizedName(): string
    {
        return match (App::getLocale()) {
            'en' => $this->name_en ?: $this->name_th,
            'zh' => $this->name_zh ?: $this->name_th,
            default => $this->name_th,
        };
    }

    public function getLocalizedDescription(): ?string
    {
        return match (App::getLocale()) {
            'en' => $this->description_en ?: $this->description_th,
            'zh' => $this->description_zh ?: $this->description_th,
            default => $this->description_th,
        };
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes ?? 0;
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 0) . ' KB';
        return $bytes ? $bytes . ' B' : '—';
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
