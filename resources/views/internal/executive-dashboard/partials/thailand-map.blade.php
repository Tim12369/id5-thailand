{{--
  Simplified regional Thailand map
  Provinces are color-coded by risk level using their region positions.
  A real choropleth map can be substituted here without changing controller/view logic.
--}}
@php
    $regionMap = [
        'North'     => ['x' => 80,  'y' => 60,  'label' => 'เหนือ'],
        'Northeast' => ['x' => 175, 'y' => 110, 'label' => 'อีสาน'],
        'Central'   => ['x' => 95,  'y' => 160, 'label' => 'กลาง'],
        'East'      => ['x' => 165, 'y' => 190, 'label' => 'ตะวันออก'],
        'South'     => ['x' => 80,  'y' => 290, 'label' => 'ใต้'],
    ];

    // Aggregate risk per region
    $regionRisk = [];
    foreach ($provinces as $prov) {
        $r = $prov->region_en ?? 'Central';
        if (!isset($regionRisk[$r])) {
            $regionRisk[$r] = ['risk' => 'low', 'count' => 0, 'label' => $prov->region_th ?? $r];
        }
        $regionRisk[$r]['count'] += $prov->total_foreigners;
        $order = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];
        if (($order[$prov->risk_level] ?? 0) > ($order[$regionRisk[$r]['risk']] ?? 0)) {
            $regionRisk[$r]['risk'] = $prov->risk_level;
        }
    }

    $riskColor = ['low' => '#10b981', 'medium' => '#f59e0b', 'high' => '#ef4444', 'critical' => '#dc2626'];
@endphp

<svg viewBox="0 0 240 420" class="w-full max-w-[180px] select-none" aria-label="Thailand regional risk map">
    {{-- Simplified Thailand outline (approximate shape) --}}
    {{-- North region --}}
    <path d="M 60 20 L 150 20 L 160 40 L 165 80 L 150 100 L 120 110 L 80 100 L 55 75 L 50 50 Z"
          fill="{{ $riskColor[$regionRisk['North']['risk'] ?? 'low'] ?? '#10b981' }}"
          fill-opacity="0.25" stroke="{{ $riskColor[$regionRisk['North']['risk'] ?? 'low'] ?? '#10b981' }}"
          stroke-width="1.5" stroke-opacity="0.7"/>
    {{-- Northeast region --}}
    <path d="M 150 100 L 165 80 L 200 90 L 210 130 L 200 180 L 165 185 L 145 170 L 140 140 L 120 110 Z"
          fill="{{ $riskColor[$regionRisk['Northeast']['risk'] ?? 'low'] ?? '#10b981' }}"
          fill-opacity="0.25" stroke="{{ $riskColor[$regionRisk['Northeast']['risk'] ?? 'low'] ?? '#10b981' }}"
          stroke-width="1.5" stroke-opacity="0.7"/>
    {{-- Central region --}}
    <path d="M 60 110 L 120 110 L 140 140 L 145 170 L 120 195 L 90 200 L 65 185 L 55 155 L 50 125 Z"
          fill="{{ $riskColor[$regionRisk['Central']['risk'] ?? 'low'] ?? '#10b981' }}"
          fill-opacity="0.25" stroke="{{ $riskColor[$regionRisk['Central']['risk'] ?? 'low'] ?? '#10b981' }}"
          stroke-width="1.5" stroke-opacity="0.7"/>
    {{-- East region --}}
    <path d="M 145 170 L 165 185 L 200 180 L 195 210 L 170 225 L 145 210 L 130 195 Z"
          fill="{{ $riskColor[$regionRisk['East']['risk'] ?? 'low'] ?? '#10b981' }}"
          fill-opacity="0.25" stroke="{{ $riskColor[$regionRisk['East']['risk'] ?? 'low'] ?? '#10b981' }}"
          stroke-width="1.5" stroke-opacity="0.7"/>
    {{-- South region (long narrow strip) --}}
    <path d="M 65 200 L 90 200 L 100 230 L 95 280 L 80 340 L 65 390 L 50 380 L 45 320 L 50 260 L 55 220 Z"
          fill="{{ $riskColor[$regionRisk['South']['risk'] ?? 'low'] ?? '#10b981' }}"
          fill-opacity="0.25" stroke="{{ $riskColor[$regionRisk['South']['risk'] ?? 'low'] ?? '#10b981' }}"
          stroke-width="1.5" stroke-opacity="0.7"/>

    {{-- Region labels --}}
    @foreach($regionRisk as $regionKey => $info)
        @php $pos = $regionMap[$regionKey] ?? null; @endphp
        @if($pos)
        <text x="{{ $pos['x'] }}" y="{{ $pos['y'] }}"
              text-anchor="middle" font-size="9"
              fill="{{ $riskColor[$info['risk']] }}"
              font-family="sans-serif" font-weight="600">
            {{ $info['label'] ?? $regionKey }}
        </text>
        @endif
    @endforeach

    {{-- Province dots --}}
    @foreach($provinces as $prov)
        @php
            // Map province codes to approximate SVG coordinates
            $dotMap = [
                'CNX' => [95, 55],  'CCO' => [65, 50],  'CRI' => [100, 35],
                'KKN' => [170, 115],'UDN' => [160, 95], 'NKP' => [155, 105],
                'BKK' => [95, 165], 'NBI' => [88, 160], 'PTE' => [85, 155],
                'CBI' => [165, 195],'PRB' => [155, 205],'CPN' => [140, 215],
                'HYI' => [60, 295], 'PKT' => [58, 310], 'KBI' => [62, 330],
                'NWT' => [70, 350], 'STN' => [68, 340], 'SNK' => [65, 320],
            ];
            $dot   = $dotMap[$prov->province_code] ?? null;
            $color = $riskColor[$prov->risk_level] ?? '#10b981';
        @endphp
        @if($dot)
            <title>{{ $prov->province_name_th }}: {{ number_format($prov->total_foreigners) }} คน ({{ $prov->risk_level }})</title>
            <circle cx="{{ $dot[0] }}" cy="{{ $dot[1] }}" r="5"
                    fill="{{ $color }}" fill-opacity="0.8"
                    stroke="{{ $color }}" stroke-width="1.5"/>
        @endif
    @endforeach

    {{-- Legend --}}
    <rect x="5" y="400" width="8" height="8" rx="2" fill="#10b981" fill-opacity="0.8"/>
    <text x="16" y="408" font-size="8" fill="rgba(255,255,255,0.5)" font-family="sans-serif">ต่ำ</text>
    <rect x="40" y="400" width="8" height="8" rx="2" fill="#f59e0b" fill-opacity="0.8"/>
    <text x="51" y="408" font-size="8" fill="rgba(255,255,255,0.5)" font-family="sans-serif">ปานกลาง</text>
    <rect x="105" y="400" width="8" height="8" rx="2" fill="#ef4444" fill-opacity="0.8"/>
    <text x="116" y="408" font-size="8" fill="rgba(255,255,255,0.5)" font-family="sans-serif">สูง</text>
    <rect x="138" y="400" width="8" height="8" rx="2" fill="#dc2626" fill-opacity="0.8"/>
    <text x="149" y="408" font-size="8" fill="rgba(255,255,255,0.5)" font-family="sans-serif">วิกฤต</text>
</svg>
