<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardCategoryStat extends Model
{
    protected $fillable = [
        'period_id', 'category', 'label_th', 'label_en', 'label_zh',
        'count', 'percentage', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'percentage' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(DashboardPeriod::class, 'period_id');
    }

    public function label(): string
    {
        return $this->label_th ?: $this->label_en;
    }
}
