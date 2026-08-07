<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class GuideLocation extends Model
{
    protected $fillable = [
        'guide_topic_id', 'type', 'label_th', 'label_en', 'label_zh',
        'url', 'station_id', 'sort_order',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(GuideTopic::class, 'guide_topic_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function getLocalizedLabel(): string
    {
        return match (App::getLocale()) {
            'en' => $this->label_en ?: $this->label_th,
            'zh' => $this->label_zh ?: $this->label_th,
            default => $this->label_th,
        };
    }
}
