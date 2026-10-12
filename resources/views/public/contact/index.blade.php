@extends('public.layouts.app')
@section('title', __('contact.title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="{{ __('layout.breadcrumb') }}">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('nav.contact') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('contact.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('contact.subtitle') }}</p>
    </div>

    {{-- Province filter --}}
    <form method="GET" action="{{ route('public.contact') }}" class="mb-6">
        @cmsEdit('terms:id5_province', 'จังหวัด', 'รายชื่อจังหวัดของสำนักงาน')<div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('public.contact') }}"
               class="px-4 py-2 text-sm rounded-xl font-medium border transition-colors
                      {{ !$province ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300' }}">
                {{ __('contact.all_provinces') }}
            </a>
            @foreach ($provinces as $p)
            <a href="{{ route('public.contact', ['province' => $p]) }}"
               class="px-4 py-2 text-sm rounded-xl font-medium border transition-colors
                      {{ $province === $p ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300' }}">
                {{ $p }}
            </a>
            @endforeach
        </div>
    </form>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Station cards --}}
        <div class="lg:col-span-2 space-y-4">
            @forelse ($stations as $station)
            @cmsEdit($station)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-sm transition-shadow">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div>
                        <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded mb-1 inline-block">
                            {{ $station->code }}
                        </span>
                        <h3 class="font-semibold text-slate-800 text-base">
                            {{ $station->getLocalizedName() }}
                        </h3>
                        <p class="text-xs text-slate-500">{{ $station->province }}</p>
                    </div>
                    @if ($station->today_hours)
                    <span class="text-xs text-green-700 bg-green-50 border border-green-200 px-2 py-1 rounded-lg shrink-0">
                        <i class="ti ti-clock mr-1"></i>{{ $station->today_hours }}
                    </span>
                    @endif
                </div>

                <ul class="space-y-1.5 text-sm text-slate-600">
                    @if ($station->getLocalizedAddress())
                    <li class="flex items-start gap-2">
                        <i class="ti ti-map-pin text-slate-400 mt-0.5 shrink-0"></i>
                        <span>{{ $station->getLocalizedAddress() }}</span>
                    </li>
                    @endif
                    @if ($station->phone)
                    <li class="flex items-center gap-2">
                        <i class="ti ti-phone text-slate-400 shrink-0"></i>
                        <a href="tel:{{ $station->phone }}" class="text-blue-700 hover:underline">
                            {{ $station->phone }}
                        </a>
                    </li>
                    @endif
                    @if ($station->email)
                    <li class="flex items-center gap-2">
                        <i class="ti ti-mail text-slate-400 shrink-0"></i>
                        <a href="mailto:{{ $station->email }}" class="text-blue-700 hover:underline truncate">
                            {{ $station->email }}
                        </a>
                    </li>
                    @endif
                </ul>

                @if ($station->lat && $station->lng)
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <a href="https://maps.google.com/?q={{ $station->lat }},{{ $station->lng }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-xs text-blue-700 hover:text-blue-800 font-medium">
                        <i class="ti ti-map-2"></i>
                        {{ __('contact.view_map') }}
                        <i class="ti ti-external-link text-xs"></i>
                    </a>
                </div>
                @endif
            </div>
            @empty
            <div class="text-center py-12 text-slate-400">
                <i class="ti ti-building-off text-4xl mb-2 block"></i>
                <p class="text-sm">{{ __('contact.no_stations') }}</p>
            </div>
            @endforelse
        </div>

        {{-- Sidebar: info + emergency --}}
        <aside>
            {{-- Emergency box --}}
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-4">
                <h3 class="font-semibold text-red-900 flex items-center gap-2 mb-2">
                    <i class="ti ti-phone text-red-600"></i>
                    {{ __('contact.emergency_line') }}
                </h3>
                @cmsEdit('setting:identity', 'สายด่วน', 'หมายเลขสายด่วน')<p class="text-3xl font-bold text-red-700 mb-1">{{ $hotline }}</p>
                <p class="text-xs text-red-700">{{ __('contact.available_247') }}</p>
                <a href="tel:{{ $hotline }}"
                   class="mt-3 block text-center bg-red-600 hover:bg-red-700 text-white
                          text-sm font-medium py-2.5 rounded-xl transition-colors">
                    {{ __('contact.call_now') }}
                </a>
            </div>

            {{-- Office hours --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2 mb-3">
                    <i class="ti ti-clock text-blue-600"></i>
                    {{ __('contact.office_hours') }}
                </h3>
                <ul class="text-sm space-y-1.5 text-slate-600">
                    <li class="flex justify-between">
                        <span>{{ __('contact.weekdays') }}</span>
                        <span class="font-medium">{{ __('contact.weekday_hours') }}</span>
                    </li>
                    <li class="flex justify-between text-slate-400">
                        <span>{{ __('contact.weekend') }}</span>
                        <span>{{ __('contact.closed') }}</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
