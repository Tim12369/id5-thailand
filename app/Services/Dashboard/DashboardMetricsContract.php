<?php

namespace App\Services\Dashboard;

use App\Models\DashboardPeriod;
use Illuminate\Support\Collection;

/**
 * Abstraction layer for dashboard data retrieval.
 * Currently backed by the database (manual entry).
 * Future integrations (Immigration System, TM30 API, Visa System) can
 * implement this interface and be swapped in via AppServiceProvider without
 * changing any controller or view code.
 */
interface DashboardMetricsContract
{
    /**
     * Get the most recent period marked as latest.
     */
    public function getLatestPeriod(): ?DashboardPeriod;

    /**
     * Get all periods, ordered by date descending (for period selector).
     */
    public function getPeriods(int $limit = 24): Collection;

    /**
     * Get all KPI records for a period, keyed by metric_key.
     */
    public function getKpis(DashboardPeriod $period): Collection;

    /**
     * Get nationality stats ordered by rank (top N).
     */
    public function getNationalityStats(DashboardPeriod $period, int $limit = 10): Collection;

    /**
     * Get immigration category breakdown for a period.
     */
    public function getCategoryStats(DashboardPeriod $period): Collection;

    /**
     * Get monthly trend data for a given year.
     */
    public function getMonthlyTrends(int $year): Collection;

    /**
     * Get provincial/area stats for a period.
     */
    public function getProvincialStats(DashboardPeriod $period): Collection;
}
