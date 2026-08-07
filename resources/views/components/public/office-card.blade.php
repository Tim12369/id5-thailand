@props([
    'office',
    'showDistance' => false,
    'highlight' => false,
])

<div
    x-data
    @if(isset($office['id']))
    x-bind:id="'office-card-' + {{ $office['id'] }}"
    @endif
    class="card card-hover p-4 transition-all duration-200 {{ $highlight ? 'ring-2 ring-blue-500 border-blue-300' : '' }}"
>
    <div class="flex items-start gap-3 mb-1.5">
        @if(!empty($office['image_url']))
        <img src="{{ $office['image_url'] }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-semibold text-slate-800 text-sm leading-snug">
                    {{ $office['name'] }}
                </h3>
                <span class="badge badge-info shrink-0">{{ $office['province'] }}</span>
            </div>
            @if(!empty($office['parent_organization']))
            <p class="text-xs text-slate-400 mt-0.5">{{ $office['parent_organization'] }}</p>
            @endif
        </div>
    </div>

    @if(!empty($office['address']))
    <p class="text-xs text-slate-500 leading-relaxed mb-2 flex items-start gap-1.5">
        <i class="ti ti-map-pin text-sm text-slate-400 mt-0.5 shrink-0"></i>
        <span>{{ $office['address'] }}</span>
    </p>
    @endif

    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mb-3">
        @if(!empty($office['phone']))
        <a href="tel:{{ $office['phone'] }}" class="flex items-center gap-1 hover:text-blue-700 focus-ring">
            <i class="ti ti-phone text-sm"></i> {{ $office['phone'] }}
        </a>
        @endif

        @if(!empty($office['today_hours']))
        <span class="flex items-center gap-1">
            <i class="ti ti-clock text-sm"></i>
            {{ __('offices.today') }}: {{ $office['today_hours'] }}
        </span>
        @else
        <span class="flex items-center gap-1 text-slate-400">
            <i class="ti ti-clock text-sm"></i>
            {{ __('offices.closed') }}
        </span>
        @endif

        @if($showDistance && isset($office['distance_km']) && $office['distance_km'] !== null && is_finite($office['distance_km']))
        <span class="badge badge-success">
            <i class="ti ti-route text-sm"></i>
            {{ number_format($office['distance_km'], 1) }} {{ __('offices.distance_km') }}
        </span>
        @endif

        @if(!empty($office['online_services']))
        <span class="badge badge-success">
            <i class="ti ti-device-laptop text-sm"></i>
            {{ __('offices.online_services') }}
        </span>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-3">
        @if(!empty($office['website']))
        <a href="{{ $office['website'] }}" target="_blank" rel="noopener"
           class="flex items-center gap-1.5 text-xs text-blue-700 hover:text-blue-800 focus-ring">
            <i class="ti ti-world text-sm"></i>
            <span class="truncate">{{ __('offices.website') }}</span>
        </a>
        @endif
        @if(!empty($office['facebook_url']))
        <a href="{{ $office['facebook_url'] }}" target="_blank" rel="noopener"
           class="flex items-center gap-1.5 text-xs text-blue-700 hover:text-blue-800 focus-ring">
            <i class="ti ti-brand-facebook text-sm"></i>
            <span>{{ __('offices.facebook') }}</span>
        </a>
        @endif
        @if(!empty($office['line_url']))
        <a href="{{ $office['line_url'] }}" target="_blank" rel="noopener"
           class="flex items-center gap-1.5 text-xs text-blue-700 hover:text-blue-800 focus-ring">
            <i class="ti ti-brand-line text-sm"></i>
            <span>{{ __('offices.line') }}</span>
        </a>
        @endif
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ $office['url'] }}" class="btn btn-secondary btn-sm flex-1">
            <i class="ti ti-info-circle text-sm"></i>
            {{ __('offices.view_details') }}
        </a>
        <a href="{{ $office['google_maps_url'] ?? ('https://www.google.com/maps/dir/?api=1&destination=' . $office['lat'] . ',' . $office['lng']) }}"
           target="_blank" rel="noopener"
           class="btn btn-primary btn-sm flex-1">
            <i class="ti ti-direction text-sm"></i>
            {{ __('offices.get_directions') }}
        </a>
    </div>
</div>
