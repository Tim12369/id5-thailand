<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Executive Dashboard — {{ $period->label() }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sarabun', 'Tahoma', sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
        h1 { font-size: 18px; font-weight: 700; color: #0f1f4d; }
        h2 { font-size: 13px; font-weight: 700; color: #0f1f4d; margin-bottom: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 3px solid #1f49c9; margin-bottom: 16px; }
        .header .subtitle { font-size: 11px; color: #475569; margin-top: 2px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 0 20px 16px; }
        .kpi-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .kpi-value { font-size: 22px; font-weight: 700; color: #0f1f4d; }
        .kpi-label { font-size: 10px; color: #64748b; margin-top: 2px; }
        .kpi-change { font-size: 11px; font-weight: 600; margin-top: 4px; }
        .up { color: #059669; } .down { color: #dc2626; }
        .section { margin: 0 20px 16px; }
        .chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 0 20px 16px; }
        .chart-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f1f5f9; padding: 5px 8px; text-align: left; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        td { padding: 4px 8px; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .badge-critical { background: #dc2626; color: #fff; }
        .footer { text-align: center; font-size: 9px; color: #94a3b8; padding: 10px; border-top: 1px solid #e2e8f0; margin-top: 16px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 10mm; size: A4 landscape; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Executive Intelligence Dashboard</h1>
            <p class="subtitle">ID5 @ Thailand 5.0 — ศูนย์กลางข้อมูลและบริการตรวจคนเข้าเมือง</p>
        </div>
        <div style="text-align:right">
            <div style="font-weight:700;color:#0f1f4d">{{ $period->label() }}</div>
            <div class="subtitle">พิมพ์เมื่อ: {{ now()->format('d/m/Y H:i') }} น.</div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid">
        @foreach([
            'total_foreigners' => ['จำนวนคนต่างด้าว', 'Total Foreigners'],
            'overstay_count'   => ['Overstay', 'Overstay Cases'],
            'tm30_compliance_rate' => ['TM30 Compliance', 'TM30 Rate'],
            'risk_alert_count' => ['Risk / Alert', 'Risk Alerts'],
        ] as $key => [$labelTh, $labelEn])
            @php $kpi = $kpis->get($key); @endphp
            <div class="kpi-card">
                <div class="kpi-label">{{ $labelTh }}</div>
                <div class="kpi-value">{{ $kpi ? $kpi->formattedValue() : '—' }}</div>
                @if($kpi?->changePercent() !== null)
                <div class="kpi-change {{ $kpi->isPositiveChange() ? 'up' : 'down' }}">
                    {{ $kpi->isPositiveChange() ? '▲' : '▼' }} {{ abs($kpi->changePercent()) }}%
                </div>
                @endif
                <div class="kpi-label">{{ $labelEn }}</div>
            </div>
        @endforeach
    </div>

    {{-- Nationalities + Categories --}}
    <div class="chart-row">
        <div class="chart-box">
            <h2>สัญชาติ Top 10</h2>
            <table>
                <thead><tr><th>#</th><th>สัญชาติ</th><th>Nationality</th><th>จำนวน</th><th>%</th></tr></thead>
                <tbody>
                @foreach($nationalities as $n)
                    <tr><td>{{ $n->rank }}</td><td>{{ $n->nationality_name_th }}</td><td>{{ $n->nationality_name_en }}</td><td style="text-align:right">{{ number_format($n->count) }}</td><td style="text-align:right">{{ $n->percentage }}%</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="chart-box">
            <h2>ประเภทการตรวจคนเข้าเมือง</h2>
            <table>
                <thead><tr><th>ประเภท</th><th>จำนวน</th><th>%</th></tr></thead>
                <tbody>
                @foreach($categories as $c)
                    <tr><td>{{ $c->label_th }}</td><td style="text-align:right">{{ number_format($c->count) }}</td><td style="text-align:right">{{ $c->percentage }}%</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Monthly Trends --}}
    @if($trends->isNotEmpty())
    <div class="section">
        <h2>แนวโน้มรายเดือน ({{ $period->period_date->year }})</h2>
        <table>
            <thead>
                <tr><th>เดือน</th><th>เข้า</th><th>ออก</th><th>Overstay</th><th>TM30 ทั้งหมด</th><th>TM30 ปฏิบัติ</th><th>TM30 %</th><th>Risk</th></tr>
            </thead>
            <tbody>
            @foreach($trends as $t)
                <tr>
                    <td>{{ $t->month_label_th }}</td>
                    <td style="text-align:right">{{ number_format($t->total_arrivals) }}</td>
                    <td style="text-align:right">{{ number_format($t->total_departures) }}</td>
                    <td style="text-align:right">{{ number_format($t->overstay_count) }}</td>
                    <td style="text-align:right">{{ number_format($t->tm30_count) }}</td>
                    <td style="text-align:right">{{ number_format($t->tm30_compliant) }}</td>
                    <td style="text-align:right">{{ $t->tm30ComplianceRate() }}%</td>
                    <td style="text-align:right">{{ number_format($t->risk_alert_count) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Provincial Stats --}}
    @if($provinces->isNotEmpty())
    <div class="section">
        <h2>ข้อมูลพื้นที่รับผิดชอบ</h2>
        <table>
            <thead>
                <tr><th>จังหวัด</th><th>Province</th><th>ภาค</th><th>คนต่างด้าว</th><th>Overstay</th><th>TM30 %</th><th>Risk</th></tr>
            </thead>
            <tbody>
            @foreach($provinces as $p)
                <tr>
                    <td>{{ $p->province_name_th }}</td>
                    <td>{{ $p->province_name_en }}</td>
                    <td>{{ $p->region_th }}</td>
                    <td style="text-align:right">{{ number_format($p->total_foreigners) }}</td>
                    <td style="text-align:right">{{ number_format($p->overstay_count) }}</td>
                    <td style="text-align:right">{{ $p->tm30_compliance_rate }}%</td>
                    <td><span class="badge badge-{{ $p->risk_level }}">{{ $p->riskLabelTh() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        ID5 @ Thailand 5.0 — Executive Intelligence Dashboard — ข้อมูลเพื่อใช้ในราชการเท่านั้น
    </div>

    <script>window.onload = function () { window.print(); }</script>
</body>
</html>
