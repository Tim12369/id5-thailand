<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

class GuideDocument extends Model
{
    protected $fillable = [
        'guide_topic_id', 'form_id', 'name_th', 'name_en', 'name_zh',
        'is_required', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(GuideTopic::class, 'guide_topic_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(PublicForm::class, 'form_id');
    }

    public function getLocalizedName(): string
    {
        return match (App::getLocale()) {
            'en' => $this->name_en ?: $this->name_th,
            'zh' => $this->name_zh ?: $this->name_th,
            default => $this->name_th,
        };
    }
}
