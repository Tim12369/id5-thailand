<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardNationalityStat extends Model
{
    protected $fillable = [
        'period_id', 'nationality_code', 'nationality_name_th', 'nationality_name_en',
        'nationality_name_zh', 'count', 'rank', 'percentage',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'rank' => 'integer',
            'percentage' => 'float',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(DashboardPeriod::class, 'period_id');
    }

    public function localeName(): string
    {
        return $this->nationality_name_th ?: $this->nationality_name_en;
    }
}
