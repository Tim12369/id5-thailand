<?php

namespace App\Http\Controllers\Internal\ExecutiveDashboard;

use App\Http\Controllers\Controller;
use App\Models\DashboardPeriod;
use App\Services\Dashboard\DashboardMetricsContract;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly DashboardMetricsContract $metrics) {}

    /**
     * Print-friendly full dashboard view for PDF generation.
     */
    public function print(DashboardPeriod $period): View
    {
        $kpis          = $this->metrics->getKpis($period);
        $nationalities = $this->metrics->getNationalityStats($period, 10);
        $categories    = $this->metrics->getCategoryStats($period);
        $trends        = $this->metrics->getMonthlyTrends($period->period_date->year);
        $provinces     = $this->metrics->getProvincialStats($period);

        return view('internal.executive-dashboard.print', compact(
            'period', 'kpis', 'nationalities', 'categories', 'trends', 'provinces'
        ));
    }

    /**
     * Export all KPI data as CSV (Excel-compatible).
     */
    public function csv(DashboardPeriod $period): StreamedResponse
    {
        $kpis          = $this->metrics->getKpis($period);
        $nationalities = $this->metrics->getNationalityStats($period, 10);
        $categories    = $this->metrics->getCategoryStats($period);
        $trends        = $this->metrics->getMonthlyTrends($period->period_date->year);
        $provinces     = $this->metrics->getProvincialStats($period);

        $filename = 'executive-dashboard-' . $period->period_date->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($kpis, $nationalities, $categories, $trends, $provinces, $period) {
            $out = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fputs($out, "\xEF\xBB\xBF");

            // Period header
            fputcsv($out, ['Executive Dashboard Export — ' . $period->label()]);
            fputcsv($out, ['Generated', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);

            // KPIs
            fputcsv($out, ['=== KEY PERFORMANCE INDICATORS ===']);
            fputcsv($out, ['Metric', 'Value', 'Previous Value', 'Change %', 'Unit']);
            foreach ($kpis as $kpi) {
                fputcsv($out, [
                    $kpi->metric_key,
                    $kpi->value,
                    $kpi->previous_value ?? '',
                    $kpi->changePercent() !== null ? $kpi->changePercent() . '%' : '',
                    $kpi->unit,
                ]);
            }
            fputcsv($out, []);

            // Nationalities
            fputcsv($out, ['=== TOP NATIONALITIES ===']);
            fputcsv($out, ['Rank', 'Code', 'Name (TH)', 'Name (EN)', 'Count', 'Percentage']);
            foreach ($nationalities as $n) {
                fputcsv($out, [$n->rank, $n->nationality_code, $n->nationality_name_th, $n->nationality_name_en, $n->count, $n->percentage . '%']);
            }
            fputcsv($out, []);

            // Categories
            fputcsv($out, ['=== IMMIGRATION CATEGORIES ===']);
            fputcsv($out, ['Category', 'Label (TH)', 'Label (EN)', 'Count', 'Percentage']);
            foreach ($categories as $c) {
                fputcsv($out, [$c->category, $c->label_th, $c->label_en, $c->count, $c->percentage . '%']);
            }
            fputcsv($out, []);

            // Monthly Trends
            fputcsv($out, ['=== MONTHLY TRENDS ===']);
            fputcsv($out, ['Year', 'Month', 'Label (EN)', 'Total Arrivals', 'Total Departures', 'Overstay', 'TM30 Total', 'TM30 Compliant', 'Risk Alerts']);
            foreach ($trends as $t) {
                fputcsv($out, [$t->year, $t->month, $t->month_label_en, $t->total_arrivals, $t->total_departures, $t->overstay_count, $t->tm30_count, $t->tm30_compliant, $t->risk_alert_count]);
            }
            fputcsv($out, []);

            // Provinces
            fputcsv($out, ['=== AREA RESPONSIBILITY DATA ===']);
            fputcsv($out, ['Province Code', 'Province (TH)', 'Province (EN)', 'Region', 'Total Foreigners', 'Overstay', 'TM30 Count', 'TM30 Compliance %', 'Risk Level']);
            foreach ($provinces as $p) {
                fputcsv($out, [$p->province_code, $p->province_name_th, $p->province_name_en, $p->region_en, $p->total_foreigners, $p->overstay_count, $p->tm30_count, $p->tm30_compliance_rate . '%', $p->risk_level]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
