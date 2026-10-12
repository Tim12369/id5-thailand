@extends('public.layouts.app')
@section('title', $office['name'])

@section('content')
@cmsEdit($station)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-6">
        <nav class="text-xs text-slate-500 mb-2" aria-label="{{ __('layout.breadcrumb') }}">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('public.offices.index') }}" class="hover:text-blue-700">{{ __('offices.title') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ $office['name'] }}</span>
        </nav>
        <a href="{{ route('public.offices.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-blue-700 hover:text-blue-800 focus-ring mb-3">
            <i class="ti ti-arrow-left"></i> {{ __('offices.back_to_list') }}
        </a>
        <div class="flex items-start gap-4">
            @if(!empty($office['image_url']))
            <img src="{{ $office['image_url'] }}" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0">
            @endif
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ $office['name'] }}</h1>
                    <span class="badge badge-info shrink-0">{{ $office['province'] }}</span>
                </div>
                @if($station->name_th !== $station->name_en && $office['name'] !== $station->name_th)
                <p class="text-slate-500 text-sm">{{ $station->name_th }}</p>
                @endif
                @if(!empty($office['parent_organization']))
                <p class="text-slate-400 text-xs mt-1">{{ $office['parent_organization'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 mb-8">

        {{-- Info --}}
        <div class="w-full lg:w-[40%] space-y-4">
            <div class="card p-5 space-y-4">
                @if(!empty($office['address']))
                <div class="flex items-start gap-3">
                    <div class="icon-tile w-9 h-9 bg-blue-50 text-blue-700">
                        <i class="ti ti-map-pin text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">{{ __('offices.address') }}</p>
                        <p class="text-sm text-slate-700 leading-relaxed">{{ $office['address'] }}</p>
                    </div>
                </div>
                @endif

                @if(!empty($office['phone']))
                <div class="flex items-start gap-3">
                    <div class="icon-tile w-9 h-9 bg-blue-50 text-blue-700">
                        <i class="ti ti-phone text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">{{ __('offices.phone') }}</p>
                        <a href="tel:{{ $office['phone'] }}" class="text-sm text-blue-700 hover:text-blue-800 focus-ring">{{ $office['phone'] }}</a>
                    </div>
                </div>
                @endif

                @if(!empty($office['email']))
                <div class="flex items-start gap-3">
                    <div class="icon-tile w-9 h-9 bg-blue-50 text-blue-700">
                        <i class="ti ti-mail text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">{{ __('offices.email') }}</p>
                        <a href="mailto:{{ $office['email'] }}" class="text-sm text-blue-700 hover:text-blue-800 focus-ring">{{ $office['email'] }}</a>
                    </div>
                </div>
                @endif

                <div class="flex items-start gap-3">
                    <div class="icon-tile w-9 h-9 bg-blue-50 text-blue-700">
                        <i class="ti ti-world text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">{{ __('offices.website') }}</p>
                        @if(!empty($office['website']))
                        <a href="{{ $office['website'] }}" target="_blank" rel="noopener" class="text-sm text-blue-700 hover:text-blue-800 focus-ring break-all">{{ $office['website'] }}</a>
                        @else
                        <p class="text-sm text-slate-400">{{ __('offices.no_website') }}</p>
                        @endif
                    </div>
                </div>

                @if(!empty($office['facebook_url']) || !empty($office['line_url']))
                <div class="flex flex-wrap gap-2">
                    @if(!empty($office['facebook_url']))
                    <a href="{{ $office['facebook_url'] }}" target="_blank" rel="noopener"
                       class="btn btn-secondary btn-sm flex-1">
                        <i class="ti ti-brand-facebook text-sm"></i> {{ __('offices.facebook') }}
                    </a>
                    @endif
                    @if(!empty($office['line_url']))
                    <a href="{{ $office['line_url'] }}" target="_blank" rel="noopener"
                       class="btn btn-secondary btn-sm flex-1">
                        <i class="ti ti-brand-line text-sm"></i> {{ __('offices.line') }}
                    </a>
                    @endif
                </div>
                @endif

                <a href="{{ $office['google_maps_url'] }}"
                   target="_blank" rel="noopener" class="btn btn-primary w-full">
                    <i class="ti ti-direction text-base"></i>
                    {{ __('offices.get_directions') }}
                </a>

                <a href="{{ !empty($office['email']) ? 'mailto:' . $office['email'] : route('public.contact') }}"
                   class="btn btn-secondary w-full">
                    <i class="ti ti-headset text-base"></i>
                    {{ __('offices.ask_office') }}
                </a>

                @if(!empty($office['online_services']) || !empty($office['last_verified_at']))
                <div class="flex flex-wrap gap-2 pt-1">
                    @if(!empty($office['online_services']))
                    <span class="badge badge-success"><i class="ti ti-device-laptop text-sm"></i> {{ __('offices.online_services') }}</span>
                    @endif
                    @if(!empty($office['last_verified_at']))
                    <span class="badge badge-neutral"><i class="ti ti-shield-check text-sm"></i> {{ __('offices.verified_on') }} {{ \Illuminate\Support\Carbon::parse($office['last_verified_at'])->format('d M Y') }}</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Opening hours --}}
            @if(!empty($office['opening_hours']))
            <div class="card p-5">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <i class="ti ti-clock text-base"></i> {{ __('offices.opening_hours') }}
                </h2>
                <div class="space-y-1.5">
                    @php $today = strtolower(now()->format('D')); @endphp
                    @foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day)
                    <div class="flex items-center justify-between text-sm py-1 px-2 rounded-lg {{ $today === $day ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-600' }}">
                        <span>{{ __('offices.days.' . $day) }}</span>
                        <span>{{ $office['opening_hours'][$day] ?? __('offices.closed') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Map --}}
        <div class="w-full lg:w-[60%]">
            <div id="office-detail-map" class="office-detail-map">
                @if (!config('services.google_maps.api_key'))
                <div class="w-full h-full flex items-center justify-center text-center px-6">
                    <p class="text-sm text-slate-400">
                        <i class="ti ti-map-off text-3xl mb-2 block"></i>
                        {{ __('offices.map_unavailable') }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Nearby offices --}}
    @if($nearby->isNotEmpty())
    <div>
        <h2 class="text-lg font-bold text-slate-900 tracking-tight mb-4">{{ __('offices.nearby_offices') }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($nearby as $nearbyOffice)
                <x-public.office-card :office="$nearbyOffice" :show-distance="true" />
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('head')
<script>
    window.OFFICE_DETAIL = @json($office);
</script>
@endpush

@push('scripts')
@if (config('services.google_maps.api_key'))
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initOfficeDetailMap"></script>
@endif
@endpush
