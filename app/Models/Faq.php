<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class Faq extends Model
{
    protected $fillable = [
        'category_id',
        'question_th', 'question_en', 'question_zh',
        'answer_th', 'answer_en', 'answer_zh',
        'is_public', 'is_active', 'sort_order', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function getLocalizedQuestion(): string
    {
        return match (App::getLocale()) {
            'en' => $this->question_en ?: $this->question_th,
            'zh' => $this->question_zh ?: $this->question_th,
            default => $this->question_th,
        };
    }

    public function getLocalizedAnswer(): string
    {
        return match (App::getLocale()) {
            'en' => $this->answer_en ?: $this->answer_th,
            'zh' => $this->answer_zh ?: $this->answer_th,
            default => $this->answer_th,
        };
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
