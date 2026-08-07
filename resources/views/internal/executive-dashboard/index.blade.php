@extends('internal.layouts.app')

@section('title', 'Executive Dashboard')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- ── Header: period selector + actions ─────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 print:hidden">
    <div>
        <h2 class="text-xl font-bold text-white">Executive Intelligence Dashboard</h2>
        <p class="text-sm text-slate-400 mt-0.5">
            ข้อมูล ณ: <span class="text-gold-500 font-medium">{{ $period->label() }}</span>
            @if($period->is_latest)
                <span class="ml-2 inline-flex items-center gap-1 text-xs text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ล่าสุด
                </span>
            @endif
        </p>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        {{-- Period Selector --}}
        @if($periods->count() > 1)
        <form method="GET" action="{{ route('internal.executive.index') }}" class="flex items-center gap-2">
            <select name="period_id" onchange="this.form.submit()" class="portal-input text-sm py-1.5 pr-8">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $period->id)>
                        {{ $p->label() }}{{ $p->is_latest ? ' ★' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
        @endif

        {{-- Export buttons --}}
        <a href="{{ route('internal.executive.export.csv', $period) }}"
           class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
            <i class="ti ti-table-export"></i> Export Excel
        </a>
        <a href="{{ route('internal.executive.export.print', $period) }}" target="_blank"
           class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
            <i class="ti ti-printer"></i> Print / PDF
        </a>
        <a href="{{ route('internal.executive.admin.index') }}"
           class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
            <i class="ti ti-settings"></i> Manage Data
        </a>
    </div>
</div>

{{-- ── KPI Cards Row ───────────────────────────────────────────── --}}
@php
    $totalForeigners   = $kpis->get('total_foreigners');
    $overstay          = $kpis->get('overstay_count');
    $tm30              = $kpis->get('tm30_compliance_rate');
    $risk              = $kpis->get('risk_alert_count');
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Total Foreigners --}}
    <div class="portal-card p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">จำนวนคนต่างด้าวทั้งหมด</span>
            <span class="w-9 h-9 rounded-xl bg-brand-500/20 flex items-center justify-center">
                <i class="ti ti-users text-brand-400 text-lg"></i>
            </span>
        </div>
        <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-white tabular-nums">
                {{ $totalForeigners ? number_format((int)$totalForeigners->value) : '—' }}
            </span>
            @if($totalForeigners?->changePercent() !== null)
                <span class="mb-1 text-sm font-medium {{ $totalForeigners->isPositiveChange() ? 'text-emerald-400' : 'text-red-400' }} flex items-center gap-0.5">
                    <i class="ti {{ $totalForeigners->isPositiveChange() ? 'ti-trending-up' : 'ti-trending-down' }} text-xs"></i>
                    {{ abs($totalForeigners->changePercent()) }}%
                </span>
            @endif
        </div>
        <span class="text-xs text-slate-500">Total Foreigners</span>
    </div>

    {{-- Overstay --}}
    <div class="portal-card p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Overstay</span>
            <span class="w-9 h-9 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <i class="ti ti-alert-triangle text-amber-400 text-lg"></i>
            </span>
        </div>
        <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-white tabular-nums">
                {{ $overstay ? number_format((int)$overstay->value) : '—' }}
            </span>
            @if($overstay?->changePercent() !== null)
                <span class="mb-1 text-sm font-medium {{ $overstay->isPositiveChange() ? 'text-red-400' : 'text-emerald-400' }} flex items-center gap-0.5">
                    <i class="ti {{ $overstay->isPositiveChange() ? 'ti-trending-up' : 'ti-trending-down' }} text-xs"></i>
                    {{ abs($overstay->changePercent()) }}%
                </span>
            @endif
        </div>
        <span class="text-xs text-slate-500">Overstay Cases</span>
    </div>

    {{-- TM30 Compliance --}}
    <div class="portal-card p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">TM30 Compliance</span>
            <span class="w-9 h-9 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                <i class="ti ti-circle-check text-emerald-400 text-lg"></i>
            </span>
        </div>
        <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-white tabular-nums">
                {{ $tm30 ? number_format($tm30->value, 1) . '%' : '—' }}
            </span>
            @if($tm30?->changePercent() !== null)
                <span class="mb-1 text-sm font-medium {{ $tm30->isPositiveChange() ? 'text-emerald-400' : 'text-red-400' }} flex items-center gap-0.5">
                    <i class="ti {{ $tm30->isPositiveChange() ? 'ti-trending-up' : 'ti-trending-down' }} text-xs"></i>
                    {{ abs($tm30->changePercent()) }}%
                </span>
            @endif
        </div>
        <span class="text-xs text-slate-500">TM30 Compliance Rate</span>
    </div>

    {{-- Risk Alert --}}
    <div class="portal-card p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Risk / Alert</span>
            <span class="w-9 h-9 rounded-xl bg-red-500/20 flex items-center justify-center">
                <i class="ti ti-shield-exclamation text-red-400 text-lg"></i>
            </span>
        </div>
        <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-white tabular-nums">
                {{ $risk ? number_format((int)$risk->value) : '—' }}
            </span>
            @if($risk?->changePercent() !== null)
                <span class="mb-1 text-sm font-medium {{ $risk->isPositiveChange() ? 'text-red-400' : 'text-emerald-400' }} flex items-center gap-0.5">
                    <i class="ti {{ $risk->isPositiveChange() ? 'ti-trending-up' : 'ti-trending-down' }} text-xs"></i>
                    {{ abs($risk->changePercent()) }}%
                </span>
            @endif
        </div>
        <span class="text-xs text-slate-500">Risk Alerts</span>
    </div>
</div>

{{-- ── Charts Row: Nationalities + Monthly Trend ──────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">

    {{-- Top 5 Nationalities (horizontal bar) --}}
    <div class="portal-card p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-white">สัญชาติ (Top 5)</h3>
            <span class="text-xs text-slate-500">ตามจำนวนผู้พักอาศัย</span>
        </div>
        @if($nationalities->isEmpty())
            <p class="text-center text-slate-500 text-sm py-8">ยังไม่มีข้อมูล</p>
        @else
            <div class="space-y-3">
                @php $maxCount = $nationalities->first()?->count ?: 1; @endphp
                @foreach($nationalities->take(5) as $idx => $nat)
                    @php
                        $colors = ['bg-brand-500', 'bg-gold-500', 'bg-emerald-500', 'bg-purple-500', 'bg-cyan-500'];
                        $color  = $colors[$idx] ?? 'bg-slate-500';
                        $pct    = round(($nat->count / $maxCount) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-slate-200">{{ $nat->rank }}. {{ $nat->nationality_name_th }}</span>
                            <span class="text-sm font-semibold text-white tabular-nums">{{ number_format($nat->count) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/5 overflow-hidden">
                            <div class="h-2 rounded-full {{ $color }} transition-all"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($nationalities->count() > 5)
            <canvas id="chartNationalities" class="mt-4" height="160"></canvas>
            @endif
        @endif
    </div>

    {{-- Monthly Arrivals Trend (line chart) --}}
    <div class="portal-card p-5 lg:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-white">แนวโน้มรายเดือน</h3>
            <span class="text-xs text-slate-500">{{ $period->period_date->year }}</span>
        </div>
        @if($trends->isEmpty())
            <p class="text-center text-slate-500 text-sm py-8">ยังไม่มีข้อมูลรายเดือน</p>
        @else
            <canvas id="chartMonthlyTrend" height="160"></canvas>
        @endif
    </div>
</div>

{{-- ── Second Charts Row: Categories + Province Map ───────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">

    {{-- Immigration Categories (donut + breakdown) --}}
    <div class="portal-card p-5 lg:col-span-2">
        <h3 class="text-sm font-semibold text-white mb-4">ประเภทการตรวจคนเข้าเมือง</h3>
        @if($categories->isEmpty())
            <p class="text-center text-slate-500 text-sm py-8">ยังไม่มีข้อมูล</p>
        @else
            <div class="flex flex-col items-center gap-4">
                <canvas id="chartCategories" class="max-w-[200px]" height="200"></canvas>
                <div class="w-full space-y-2">
                    @php
                        $catColors = ['#2f64e8','#d9a437','#10b981','#8b5cf6','#ef4444','#64748b'];
                    @endphp
                    @foreach($categories as $ci => $cat)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $catColors[$ci % count($catColors)] }}"></span>
                            <span class="text-sm text-slate-300 truncate">{{ $cat->label_th }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <span class="text-sm font-medium text-white tabular-nums">{{ number_format($cat->count) }}</span>
                            <span class="text-xs text-slate-500 w-10 text-right">{{ number_format($cat->percentage, 1) }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Area Responsibility / Risk Map ──────────────────────── --}}
    <div class="portal-card p-5 lg:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-white">แผนที่พื้นที่รับผิดชอบ</h3>
            <span class="text-xs text-slate-500">Risk Map</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Simplified Thailand regional map (SVG) --}}
            <div class="flex justify-center">
                @include('internal.executive-dashboard.partials.thailand-map', ['provinces' => $provinces])
            </div>

            {{-- Province list with risk badges --}}
            <div class="space-y-1.5 overflow-y-auto max-h-64">
                @if($provinces->isEmpty())
                    <p class="text-center text-slate-500 text-sm py-8">ยังไม่มีข้อมูล</p>
                @else
                    @foreach($provinces as $prov)
                    <div class="flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 bg-white/3 hover:bg-white/6 transition-colors">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-200 truncate">{{ $prov->province_name_th }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($prov->total_foreigners) }} คน</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="badge {{ $prov->riskBadgeClass() }} text-[10px]">{{ $prov->riskLabelTh() }}</span>
                            <span class="text-[11px] text-slate-400">{{ $prov->tm30_compliance_rate }}% TM30</span>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── TM30 + Overstay trend row ───────────────────────────────── --}}
@if($trends->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="portal-card p-5">
        <h3 class="text-sm font-semibold text-white mb-4">Overstay รายเดือน</h3>
        <canvas id="chartOverstay" height="120"></canvas>
    </div>
    <div class="portal-card p-5">
        <h3 class="text-sm font-semibold text-white mb-4">TM30 Compliance รายเดือน</h3>
        <canvas id="chartTm30" height="120"></canvas>
    </div>
</div>
@endif

{{-- ── Footer: last updated ───────────────────────────────────── --}}
<p class="text-center text-xs text-slate-600 mt-6">
    ข้อมูลอัปเดตล่าสุด: {{ $period->updated_at->format('d/m/Y H:i') }} น. —
    ID5 @ Thailand 5.0 Executive Intelligence
</p>

@endsection

@push('scripts')
<script>
(function () {
    const dark = {
        color:     'rgba(255,255,255,0.7)',
        grid:      'rgba(255,255,255,0.06)',
        tick:      'rgba(255,255,255,0.4)',
    };

    Chart.defaults.color = dark.color;
    Chart.defaults.borderColor = dark.grid;
    Chart.defaults.font.family = 'Instrument Sans, sans-serif';

    function makeGradient(ctx, color1, color2) {
        const g = ctx.createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0, color1);
        g.addColorStop(1, color2);
        return g;
    }

    // ── Monthly Trend chart ───────────────────────────────────
    @if($trends->isNotEmpty())
    (function () {
        const labels   = @json($trends->pluck('month_label_th'));
        const arrivals = @json($trends->pluck('total_arrivals'));
        const depart   = @json($trends->pluck('total_departures'));

        const ctx = document.getElementById('chartMonthlyTrend')?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'เข้า',
                        data: arrivals,
                        borderColor: '#2f64e8',
                        backgroundColor: makeGradient(ctx, 'rgba(47,100,232,0.3)', 'rgba(47,100,232,0)'),
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2f64e8',
                        pointRadius: 4,
                    },
                    {
                        label: 'ออก',
                        data: depart,
                        borderColor: '#d9a437',
                        backgroundColor: makeGradient(ctx, 'rgba(217,164,55,0.2)', 'rgba(217,164,55,0)'),
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#d9a437',
                        pointRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { color: dark.color, usePointStyle: true, pointStyleWidth: 8 } } },
                scales: {
                    x: { ticks: { color: dark.tick }, grid: { color: dark.grid } },
                    y: { ticks: { color: dark.tick, callback: v => v.toLocaleString() }, grid: { color: dark.grid } },
                },
            },
        });
    })();
    @endif

    // ── Category Donut ────────────────────────────────────────
    @if($categories->isNotEmpty())
    (function () {
        const labels  = @json($categories->pluck('label_th'));
        const data    = @json($categories->pluck('count'));
        const colors  = ['#2f64e8','#d9a437','#10b981','#8b5cf6','#ef4444','#64748b'];

        const ctx = document.getElementById('chartCategories')?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: 'rgba(255,255,255,0.05)', hoverBorderColor: '#fff' }] },
            options: {
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()}` } },
                },
            },
        });
    })();
    @endif

    // ── Overstay mini chart ───────────────────────────────────
    @if($trends->isNotEmpty())
    (function () {
        const labels   = @json($trends->pluck('month_label_th'));
        const overstay = @json($trends->pluck('overstay_count'));

        const ctx = document.getElementById('chartOverstay')?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Overstay',
                    data: overstay,
                    backgroundColor: 'rgba(245,158,11,0.7)',
                    borderColor: '#d9a437',
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: dark.tick, font: { size: 11 } }, grid: { color: dark.grid } },
                    y: { ticks: { color: dark.tick, callback: v => v.toLocaleString() }, grid: { color: dark.grid } },
                },
            },
        });
    })();

    // ── TM30 Compliance mini chart ────────────────────────────
    (function () {
        const labels = @json($trends->pluck('month_label_th'));
        const rates  = @json($trends->map(fn($t) => $t->tm30ComplianceRate()));

        const ctx = document.getElementById('chartTm30')?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'TM30 %',
                    data: rates,
                    borderColor: '#10b981',
                    backgroundColor: makeGradient(ctx, 'rgba(16,185,129,0.3)', 'rgba(16,185,129,0)'),
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: dark.tick, font: { size: 11 } }, grid: { color: dark.grid } },
                    y: { min: 0, max: 100, ticks: { color: dark.tick, callback: v => v + '%' }, grid: { color: dark.grid } },
                },
            },
        });
    })();
    @endif
})();
</script>
@endpush
