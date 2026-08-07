{{--
  Thailand Operations Map — plots every monitored office by its real lat/lng,
  color-coded by live ops_status (green/yellow/red). Self-contained inline SVG,
  same approach as the Executive Dashboard's regional risk map, so this has
  zero external map-API dependency and works without billing/keys.
--}}
@php
    $points = $offices->filter(fn ($o) => $o->lat !== null && $o->lng !== null);

    $minLat = $points->min('lat') ?? 5.6;
    $maxLat = $points->max('lat') ?? 20.5;
    $minLng = $points->min('lng') ?? 97.3;
    $maxLng = $points->max('lng') ?? 105.6;

    // Pad the bounding box so edge points aren't flush against the border.
    $latPad = max(($maxLat - $minLat) * 0.15, 0.3);
    $lngPad = max(($maxLng - $minLng) * 0.15, 0.3);
    $minLat -= $latPad; $maxLat += $latPad;
    $minLng -= $lngPad; $maxLng += $lngPad;

    $w = 360; $h = 280;

    $project = function ($lat, $lng) use ($minLat, $maxLat, $minLng, $maxLng, $w, $h) {
        $x = (($lng - $minLng) / max($maxLng - $minLng, 0.0001)) * $w;
        $y = $h - ((($lat - $minLat) / max($maxLat - $minLat, 0.0001)) * $h); // flip: higher lat = lower y
        return [round($x, 1), round($y, 1)];
    };

    $colors = ['normal' => '#10b981', 'warning' => '#f59e0b', 'incident' => '#ef4444'];
@endphp

<div class="relative">
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto rounded-xl bg-white/5 border border-white/10" aria-label="Thailand operations map">
        @foreach ($points as $office)
            @php
                [$x, $y] = $project($office->lat, $office->lng);
                $color = $colors[$office->ops_status] ?? $colors['normal'];
                $isIncident = $office->ops_status === 'incident';
            @endphp
            @if ($isIncident)
                <circle cx="{{ $x }}" cy="{{ $y }}" r="10" fill="{{ $color }}" fill-opacity="0.25">
                    <animate attributeName="r" values="8;14;8" dur="1.8s" repeatCount="indefinite" />
                    <animate attributeName="fill-opacity" values="0.35;0.05;0.35" dur="1.8s" repeatCount="indefinite" />
                </circle>
            @endif
            <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="{{ $color }}" fill-opacity="0.85"
                    stroke="white" stroke-opacity="0.4" stroke-width="1">
                <title>{{ $office->getLocalizedName() }} — {{ ucfirst($office->ops_status) }} ({{ $office->active_officers }} officers, {{ $office->online_cameras }} cameras online)</title>
            </circle>
        @endforeach
    </svg>

    <div class="absolute bottom-2 left-2 flex items-center gap-3 bg-black/30 backdrop-blur-sm rounded-lg px-3 py-1.5">
        @foreach (['normal' => 'Normal', 'warning' => 'Warning', 'incident' => 'Incident'] as $key => $label)
        <span class="flex items-center gap-1.5 text-[11px] text-slate-200">
            <span class="w-2 h-2 rounded-full" style="background:{{ $colors[$key] }}"></span>{{ $label }}
        </span>
        @endforeach
    </div>
</div>
