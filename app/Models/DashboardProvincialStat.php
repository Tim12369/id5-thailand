<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardProvincialStat extends Model
{
    protected $fillable = [
        'period_id', 'province_code', 'province_name_th', 'province_name_en',
        'region_th', 'region_en', 'total_foreigners', 'overstay_count',
        'tm30_count', 'tm30_compliance_rate', 'risk_level', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'total_foreigners' => 'integer',
            'overstay_count' => 'integer',
            'tm30_count' => 'integer',
            'tm30_compliance_rate' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(DashboardPeriod::class, 'period_id');
    }

    public function riskBadgeClass(): string
    {
        return match ($this->risk_level) {
            'critical' => 'badge-danger',
            'high'     => 'badge-warning',
            'medium'   => 'badge-info',
            default    => 'badge-neutral',
        };
    }

    public function riskLabelTh(): string
    {
        return match ($this->risk_level) {
            'critical' => 'วิกฤต',
            'high'     => 'สูง',
            'medium'   => 'ปานกลาง',
            default    => 'ต่ำ',
        };
    }
}
