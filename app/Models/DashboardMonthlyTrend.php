<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DashboardMonthlyTrend extends Model
{
    protected $fillable = [
        'year', 'month', 'month_label_th', 'month_label_en',
        'total_arrivals', 'total_departures', 'overstay_count',
        'tm30_count', 'tm30_compliant', 'risk_alert_count',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'total_arrivals' => 'integer',
            'total_departures' => 'integer',
            'overstay_count' => 'integer',
            'tm30_count' => 'integer',
            'tm30_compliant' => 'integer',
            'risk_alert_count' => 'integer',
        ];
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year)->orderBy('month');
    }

    public function tm30ComplianceRate(): float
    {
        if ($this->tm30_count === 0) return 0;
        return round(($this->tm30_compliant / $this->tm30_count) * 100, 1);
    }
}
