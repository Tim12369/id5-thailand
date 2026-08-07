<?php

namespace App\Http\Controllers\Internal\ExecutiveDashboard;

use App\Http\Controllers\Controller;
use App\Models\DashboardAuditLog;
use App\Models\DashboardCategoryStat;
use App\Models\DashboardKpi;
use App\Models\DashboardMonthlyTrend;
use App\Models\DashboardNationalityStat;
use App\Models\DashboardPeriod;
use App\Models\DashboardProvincialStat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    // ─── Periods ─────────────────────────────────────────────────

    public function index(): View
    {
        $periods = DashboardPeriod::with('creator')
            ->orderByDesc('period_date')
            ->paginate(20);

        $auditLogs = DashboardAuditLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('internal.executive-dashboard.admin.index', compact('periods', 'auditLogs'));
    }

    public function create(): View
    {
        return view('internal.executive-dashboard.admin.period-form', ['period' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_type' => ['required', 'in:daily,weekly,monthly,yearly'],
            'period_date' => ['required', 'date'],
            'label_th'    => ['nullable', 'string', 'max:100'],
            'label_en'    => ['nullable', 'string', 'max:100'],
            'is_latest'   => ['boolean'],
        ]);

        if (!empty($data['is_latest'])) {
            DashboardPeriod::where('is_latest', true)->update(['is_latest' => false]);
        }

        $period = DashboardPeriod::create(array_merge($data, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        $this->seedEmptyKpis($period);

        DashboardAuditLog::record('create_period', "Created period: {$period->label()}", DashboardPeriod::class, $period->id, [], $data);

        return redirect()->route('internal.executive.admin.edit', $period)
            ->with('success', 'Period created. Now enter the data below.');
    }

    public function edit(DashboardPeriod $period): View
    {
        $period->load(['kpis', 'nationalityStats', 'categoryStats', 'provincialStats']);

        $trends = DashboardMonthlyTrend::forYear($period->period_date->year)->get();

        return view('internal.executive-dashboard.admin.edit', compact('period', 'trends'));
    }

    public function update(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $data = $request->validate([
            'period_type' => ['required', 'in:daily,weekly,monthly,yearly'],
            'period_date' => ['required', 'date'],
            'label_th'    => ['nullable', 'string', 'max:100'],
            'label_en'    => ['nullable', 'string', 'max:100'],
            'is_latest'   => ['boolean'],
        ]);

        if (!empty($data['is_latest']) && !$period->is_latest) {
            DashboardPeriod::where('is_latest', true)->update(['is_latest' => false]);
        }

        $old = $period->only(['period_type', 'period_date', 'label_th', 'label_en', 'is_latest']);
        $period->update(array_merge($data, ['updated_by' => auth()->id()]));

        DashboardAuditLog::record('update_period', "Updated period: {$period->label()}", DashboardPeriod::class, $period->id, $old, $data);

        return back()->with('success', 'Period updated.');
    }

    public function setLatest(DashboardPeriod $period): RedirectResponse
    {
        DashboardPeriod::where('is_latest', true)->update(['is_latest' => false]);
        $period->update(['is_latest' => true, 'updated_by' => auth()->id()]);

        DashboardAuditLog::record('set_latest', "Set period as latest: {$period->label()}", DashboardPeriod::class, $period->id);

        return back()->with('success', "'{$period->label()}' is now the active period.");
    }

    public function destroy(DashboardPeriod $period): RedirectResponse
    {
        DashboardAuditLog::record('delete_period', "Deleted period: {$period->label()}", DashboardPeriod::class, $period->id);
        $period->delete();

        return redirect()->route('internal.executive.admin.index')
            ->with('success', 'Period deleted.');
    }

    // ─── KPIs ────────────────────────────────────────────────────

    public function updateKpis(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $rules = [];
        $kpiKeys = ['total_foreigners', 'overstay_count', 'tm30_compliance_rate', 'risk_alert_count'];

        foreach ($kpiKeys as $key) {
            $rules["kpis.{$key}.value"]          = ['required', 'numeric', 'min:0'];
            $rules["kpis.{$key}.previous_value"]  = ['nullable', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        foreach ($validated['kpis'] as $key => $values) {
            $unit = $key === 'tm30_compliance_rate' ? 'percent' : 'count';
            $old = DashboardKpi::where(['period_id' => $period->id, 'metric_key' => $key])->first()?->toArray() ?? [];

            DashboardKpi::updateOrCreate(
                ['period_id' => $period->id, 'metric_key' => $key],
                array_merge($values, ['unit' => $unit])
            );

            DashboardAuditLog::record('update_kpi', "Updated KPI '{$key}' for {$period->label()}", DashboardKpi::class, $period->id, $old, $values);
        }

        return back()->with('success', 'KPIs updated successfully.');
    }

    // ─── Nationality Stats ────────────────────────────────────────

    public function updateNationalities(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $request->validate([
            'nationalities'                       => ['required', 'array', 'min:1'],
            'nationalities.*.nationality_code'    => ['required', 'string', 'max:10'],
            'nationalities.*.nationality_name_th' => ['required', 'string', 'max:100'],
            'nationalities.*.nationality_name_en' => ['required', 'string', 'max:100'],
            'nationalities.*.nationality_name_zh' => ['nullable', 'string', 'max:100'],
            'nationalities.*.count'               => ['required', 'integer', 'min:0'],
            'nationalities.*.percentage'          => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $period->nationalityStats()->delete();

        foreach ($request->input('nationalities') as $rank => $row) {
            DashboardNationalityStat::create(array_merge($row, [
                'period_id' => $period->id,
                'rank'      => $rank + 1,
            ]));
        }

        DashboardAuditLog::record('update_nationalities', "Updated nationality stats for {$period->label()}", DashboardPeriod::class, $period->id);

        return back()->with('success', 'Nationality statistics updated.');
    }

    // ─── Category Stats ───────────────────────────────────────────

    public function updateCategories(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $request->validate([
            'categories'              => ['required', 'array', 'min:1'],
            'categories.*.category'   => ['required', 'string', 'max:50'],
            'categories.*.label_th'   => ['required', 'string', 'max:100'],
            'categories.*.label_en'   => ['required', 'string', 'max:100'],
            'categories.*.label_zh'   => ['nullable', 'string', 'max:100'],
            'categories.*.count'      => ['required', 'integer', 'min:0'],
            'categories.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $period->categoryStats()->delete();

        foreach ($request->input('categories') as $order => $row) {
            DashboardCategoryStat::create(array_merge($row, [
                'period_id'  => $period->id,
                'sort_order' => $order,
            ]));
        }

        DashboardAuditLog::record('update_categories', "Updated category stats for {$period->label()}", DashboardPeriod::class, $period->id);

        return back()->with('success', 'Category breakdown updated.');
    }

    // ─── Monthly Trends ───────────────────────────────────────────

    public function updateTrends(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $request->validate([
            'trends'                    => ['required', 'array'],
            'trends.*.year'             => ['required', 'integer'],
            'trends.*.month'            => ['required', 'integer', 'min:1', 'max:12'],
            'trends.*.month_label_th'   => ['required', 'string', 'max:20'],
            'trends.*.month_label_en'   => ['required', 'string', 'max:20'],
            'trends.*.total_arrivals'   => ['required', 'integer', 'min:0'],
            'trends.*.total_departures' => ['required', 'integer', 'min:0'],
            'trends.*.overstay_count'   => ['required', 'integer', 'min:0'],
            'trends.*.tm30_count'       => ['required', 'integer', 'min:0'],
            'trends.*.tm30_compliant'   => ['required', 'integer', 'min:0'],
            'trends.*.risk_alert_count' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('trends') as $row) {
            DashboardMonthlyTrend::updateOrCreate(
                ['year' => $row['year'], 'month' => $row['month']],
                $row
            );
        }

        DashboardAuditLog::record('update_trends', "Updated monthly trends for year {$period->period_date->year}", DashboardPeriod::class, $period->id);

        return back()->with('success', 'Monthly trends updated.');
    }

    // ─── Provincial Stats ─────────────────────────────────────────

    public function updateProvinces(Request $request, DashboardPeriod $period): RedirectResponse
    {
        $request->validate([
            'provinces'                          => ['required', 'array', 'min:1'],
            'provinces.*.province_code'          => ['required', 'string', 'max:10'],
            'provinces.*.province_name_th'       => ['required', 'string', 'max:100'],
            'provinces.*.province_name_en'       => ['required', 'string', 'max:100'],
            'provinces.*.region_th'              => ['nullable', 'string', 'max:100'],
            'provinces.*.region_en'              => ['nullable', 'string', 'max:100'],
            'provinces.*.total_foreigners'       => ['required', 'integer', 'min:0'],
            'provinces.*.overstay_count'         => ['required', 'integer', 'min:0'],
            'provinces.*.tm30_count'             => ['required', 'integer', 'min:0'],
            'provinces.*.tm30_compliance_rate'   => ['required', 'numeric', 'min:0', 'max:100'],
            'provinces.*.risk_level'             => ['required', 'in:low,medium,high,critical'],
        ]);

        $period->provincialStats()->delete();

        foreach ($request->input('provinces') as $order => $row) {
            DashboardProvincialStat::create(array_merge($row, [
                'period_id'  => $period->id,
                'sort_order' => $order,
            ]));
        }

        DashboardAuditLog::record('update_provinces', "Updated provincial stats for {$period->label()}", DashboardPeriod::class, $period->id);

        return back()->with('success', 'Area responsibility data updated.');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function seedEmptyKpis(DashboardPeriod $period): void
    {
        $defaults = [
            'total_foreigners'    => ['unit' => 'count'],
            'overstay_count'      => ['unit' => 'count'],
            'tm30_compliance_rate'=> ['unit' => 'percent'],
            'risk_alert_count'    => ['unit' => 'count'],
        ];

        foreach ($defaults as $key => $extra) {
            DashboardKpi::create(array_merge(['period_id' => $period->id, 'metric_key' => $key, 'value' => 0], $extra));
        }
    }
}
