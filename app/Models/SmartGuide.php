<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class SmartGuide extends Model
{
    protected $fillable = [
        'persona', 'topic', 'dimension',
        'content_th', 'content_en', 'content_zh',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const PERSONAS = [
        'tourist', 'student', 'worker', 'investor',
        'retiree', 'family', 'thai_citizen', 'hotel', 'employer', 'general', 'officer',
    ];

    public const TOPICS = [
        'visa', 'tm30', '90day', 'extension', 'overstay', 'entry', 'exit', 'work_permit',
        'investor_visa', 'marriage_visa',
    ];

    public const DIMENSIONS = ['who', 'what', 'why', 'where', 'when', 'how'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForPersona(Builder $q, string $persona): Builder
    {
        return $q->where('persona', $persona);
    }

    public function scopeForTopic(Builder $q, string $topic): Builder
    {
        return $q->where('topic', $topic);
    }

    public function getLocalizedContent(): string
    {
        return match (App::getLocale()) {
            'en' => $this->content_en ?: $this->content_th,
            'zh' => $this->content_zh ?: $this->content_th,
            default => $this->content_th,
        };
    }
}
