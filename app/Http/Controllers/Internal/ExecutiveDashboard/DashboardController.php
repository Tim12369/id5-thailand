<?php

namespace App\Http\Controllers\Internal\ExecutiveDashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsContract;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsContract $metrics) {}

    public function index(Request $request): View
    {
        $periods = $this->metrics->getPeriods(24);
        $period  = $periods->firstWhere('is_latest', true) ?? $periods->first();

        // Allow period override via query string
        if ($request->filled('period_id')) {
            $period = $periods->firstWhere('id', (int) $request->integer('period_id')) ?? $period;
        }

        if (!$period) {
            return view('internal.executive-dashboard.empty');
        }

        $kpis          = $this->metrics->getKpis($period);
        $nationalities = $this->metrics->getNationalityStats($period, 10);
        $categories    = $this->metrics->getCategoryStats($period);
        $trends        = $this->metrics->getMonthlyTrends($period->period_date->year);
        $provinces     = $this->metrics->getProvincialStats($period);

        return view('internal.executive-dashboard.index', compact(
            'period', 'periods', 'kpis', 'nationalities', 'categories', 'trends', 'provinces'
        ));
    }
}
