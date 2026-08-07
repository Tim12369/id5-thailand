<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class GuideStep extends Model
{
    protected $fillable = [
        'guide_topic_id', 'step_number',
        'title_th', 'title_en', 'title_zh',
        'description_th', 'description_en', 'description_zh',
        'sort_order',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(GuideTopic::class, 'guide_topic_id');
    }

    public function getLocalizedTitle(): string
    {
        return match (App::getLocale()) {
            'en' => $this->title_en ?: $this->title_th,
            'zh' => $this->title_zh ?: $this->title_th,
            default => $this->title_th,
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
}
