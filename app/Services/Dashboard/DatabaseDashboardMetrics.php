<?php

namespace App\Services\Dashboard;

use App\Models\DashboardCategoryStat;
use App\Models\DashboardKpi;
use App\Models\DashboardMonthlyTrend;
use App\Models\DashboardNationalityStat;
use App\Models\DashboardPeriod;
use App\Models\DashboardProvincialStat;
use Illuminate\Support\Collection;

class DatabaseDashboardMetrics implements DashboardMetricsContract
{
    public function getLatestPeriod(): ?DashboardPeriod
    {
        return DashboardPeriod::where('is_latest', true)->latest('period_date')->first();
    }

    public function getPeriods(int $limit = 24): Collection
    {
        return DashboardPeriod::orderByDesc('period_date')->limit($limit)->get();
    }

    public function getKpis(DashboardPeriod $period): Collection
    {
        return DashboardKpi::where('period_id', $period->id)->get()->keyBy('metric_key');
    }

    public function getNationalityStats(DashboardPeriod $period, int $limit = 10): Collection
    {
        return DashboardNationalityStat::where('period_id', $period->id)
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    public function getCategoryStats(DashboardPeriod $period): Collection
    {
        return DashboardCategoryStat::where('period_id', $period->id)
            ->orderBy('sort_order')
            ->get();
    }

    public function getMonthlyTrends(int $year): Collection
    {
        return DashboardMonthlyTrend::forYear($year)->get();
    }

    public function getProvincialStats(DashboardPeriod $period): Collection
    {
        return DashboardProvincialStat::where('period_id', $period->id)
            ->orderBy('sort_order')
            ->get();
    }
}
