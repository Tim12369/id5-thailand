@extends('public.layouts.app')
@section('title', __('offices.title'))

@section('content')
@cmsEdit('list:id5_office', 'สำนักงาน', 'สำนักงานตรวจคนเข้าเมืองทั้งหมด')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     x-data="officeLocator(@js($offices), @js($provinces), @js($regions))">

    <div class="mb-6">
        <nav class="text-xs text-slate-500 mb-2" aria-label="{{ __('layout.breadcrumb') }}">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('offices.title') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('offices.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('offices.subtitle') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
            <input type="text" x-model="search"
                   placeholder="{{ __('offices.search_placeholder') }}"
                   class="focus-ring w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm
                          placeholder:text-slate-400 focus:border-blue-300">
        </div>

        <select x-model="region"
                class="focus-ring rounded-xl border border-slate-200 text-sm px-4 py-2.5 bg-white sm:w-48">
            <option value="">{{ __('offices.all_regions') }}</option>
            @foreach ($regions as $region)
                <option value="{{ $region }}">{{ $region }}</option>
            @endforeach
        </select>

        <select x-model="province"
                class="focus-ring rounded-xl border border-slate-200 text-sm px-4 py-2.5 bg-white sm:w-56">
            <option value="">{{ __('offices.all_provinces') }}</option>
            @foreach ($provinces as $province)
                <option value="{{ $province }}">{{ $province }}</option>
            @endforeach
        </select>

        <button type="button" @click="findNearMe()" :disabled="locating"
                class="btn btn-primary shrink-0">
            <i class="ti ti-current-location text-base" :class="locating ? 'animate-spin' : ''"></i>
            <span x-text="locating ? @jsText(__('offices.locating')) : @jsText(__('offices.near_me'))"></span>
        </button>
    </div>

    <p x-show="locationError" x-cloak class="text-sm text-red-600 mb-4">
        <i class="ti ti-alert-circle mr-1"></i>{{ __('offices.location_error') }}
    </p>

    {{-- List + Map --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- List --}}
        <div class="w-full lg:w-[40%]">
            <div class="office-list">
                <template x-if="filtered.length === 0">
                    <div class="text-center py-16 text-slate-400">
                        <i class="ti ti-map-pin-off text-4xl mb-3 block"></i>
                        <p class="text-sm">{{ __('offices.no_results') }}</p>
                    </div>
                </template>

                <template x-for="office in filtered" :key="office.id">
                    <div @click="selectOffice(office)" class="cursor-pointer"
                         :class="selectedId === office.id ? 'ring-2 ring-blue-500 rounded-2xl' : ''">
                        <div class="card card-hover p-4">
                            <div class="flex items-start gap-3 mb-1.5">
                                <img :src="office.image_url" alt="" x-show="office.image_url" class="w-10 h-10 rounded-lg object-cover shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="font-semibold text-slate-800 text-sm leading-snug" x-text="office.name"></h3>
                                        <span class="badge badge-info shrink-0" x-text="office.province"></span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 leading-relaxed mb-2 flex items-start gap-1.5" x-show="office.address">
                                <i class="ti ti-map-pin text-sm text-slate-400 mt-0.5 shrink-0"></i>
                                <span x-text="office.address"></span>
                            </p>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mb-3">
                                <a :href="'tel:' + office.phone" @click.stop
                                   class="flex items-center gap-1 hover:text-blue-700 focus-ring" x-show="office.phone">
                                    <i class="ti ti-phone text-sm"></i> <span x-text="office.phone"></span>
                                </a>

                                <template x-if="office.today_hours">
                                    <span class="flex items-center gap-1">
                                        <i class="ti ti-clock text-sm"></i>
                                        {{ __('offices.today') }}: <span x-text="office.today_hours"></span>
                                    </span>
                                </template>
                                <template x-if="!office.today_hours">
                                    <span class="flex items-center gap-1 text-slate-400">
                                        <i class="ti ti-clock text-sm"></i>
                                        {{ __('offices.closed') }}
                                    </span>
                                </template>

                                <template x-if="office.distance_km != null">
                                    <span class="badge badge-success">
                                        <i class="ti ti-route text-sm"></i>
                                        <span x-text="office.distance_km.toFixed(1)"></span> {{ __('offices.distance_km') }}
                                    </span>
                                </template>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <a :href="office.website" target="_blank" rel="noopener" @click.stop
                                   x-show="office.website"
                                   class="flex items-center gap-1.5 text-xs text-blue-700 hover:text-blue-800 focus-ring">
                                    <i class="ti ti-world text-sm"></i>
                                    <span>{{ __('offices.website') }}</span>
                                </a>
                                <span x-show="!office.website" class="text-xs text-slate-400">{{ __('offices.no_website') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a :href="office.url" @click.stop class="btn btn-secondary btn-sm flex-1">
                                    <i class="ti ti-info-circle text-sm"></i>
                                    {{ __('offices.view_details') }}
                                </a>
                                <a :href="directionsUrl(office)" target="_blank" rel="noopener" @click.stop
                                   class="btn btn-primary btn-sm flex-1">
                                    <i class="ti ti-direction text-sm"></i>
                                    {{ __('offices.get_directions') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Map --}}
        <div class="w-full lg:w-[60%]">
            <div id="office-map" class="office-map">
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
</div>
@endsection

@push('head')
<script>
    window.OFFICE_DATA = @json($offices);
</script>
@endpush

@push('scripts')
@if (config('services.google_maps.api_key'))
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initOfficeMap"></script>
@endif
@endpush
