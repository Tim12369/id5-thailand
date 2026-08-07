@extends('public.layouts.app')
@section('title', __("{$lang}.title"))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <nav class="text-xs text-slate-500 flex items-center gap-1.5 mb-6" aria-label="Breadcrumb">
        <a href="{{ route('public.home') }}" class="focus-ring hover:text-blue-700">{{ __('nav.home') }}</a>
        <span>/</span>
        <span>{{ __("{$lang}.title") }}</span>
    </nav>

    <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">{{ __("{$lang}.title") }}</h1>
        <p class="text-slate-500 text-sm mt-1">{{ __("{$lang}.subtitle") }}</p>
    </div>

    {{-- ── Intro cards — same structural pattern on every division page ── --}}
    <div class="grid sm:grid-cols-2 gap-5 mb-10">
        <div class="card p-6">
            <div class="icon-tile w-11 h-11 bg-slate-100 text-slate-700 mb-4">
                <i class="ti {{ $theme['icon'] }} text-xl"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800 mb-2">{{ __("{$lang}.what_is_title") }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">{{ __("{$lang}.what_is_desc") }}</p>
        </div>

        <div class="card p-6">
            <div class="icon-tile w-11 h-11 bg-slate-100 text-slate-700 mb-4">
                <i class="ti ti-target-arrow text-xl"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800 mb-2">{{ __("{$lang}.mission_title") }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed">{{ __("{$lang}.mission_desc") }}</p>
        </div>
    </div>

    {{-- ── Letterhead panel — same visual grammar as the About Us org chart
         (ornate bordered panel, banner ribbon, tiered leadership cards,
         backed by the same OrgChartSection/Tier/Member data and admin
         screen) but each division gets its own palette, echoing that
         division's card color on the homepage. ── --}}
    <div class="relative overflow-hidden rounded-3xl border-2 {{ $theme['border'] }} {{ $theme['panelBg'] }} p-5 sm:p-9 shadow-2xl ring-1 ring-black/10">

        {{-- Fine grid texture, tinted per division --}}
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background-image:
                linear-gradient({{ $theme['patternColor'] }} 1px, transparent 1px),
                linear-gradient(90deg, {{ $theme['patternColor'] }} 1px, transparent 1px);
                background-size: 26px 26px;">
        </div>

        {{-- Spotlight glow --}}
        <div class="absolute inset-x-0 top-0 h-72 pointer-events-none {{ $theme['glow'] }}"></div>

        {{-- Corner flourishes --}}
        <span class="absolute top-3 left-3 {{ $theme['corner'] }} text-lg leading-none select-none pointer-events-none">◇</span>
        <span class="absolute top-3 right-3 {{ $theme['corner'] }} text-lg leading-none select-none pointer-events-none">◇</span>
        <span class="absolute bottom-3 left-3 {{ $theme['corner'] }} text-lg leading-none select-none pointer-events-none">◇</span>
        <span class="absolute bottom-3 right-3 {{ $theme['corner'] }} text-lg leading-none select-none pointer-events-none">◇</span>

        <div class="relative">
            {{-- Banner --}}
            <div class="text-center mb-9">
                <div class="inline-flex items-center gap-3 bg-linear-to-r {{ $theme['banner'] }} rounded-full px-6 sm:px-10 py-3 font-bold text-sm sm:text-lg shadow-[0_6px_20px_-4px_rgba(0,0,0,0.5)] border-2 ring-2">
                    <i class="ti {{ $theme['icon'] }}"></i>
                    <span class="drop-shadow-sm">{{ __("{$lang}.org_banner") }}</span>
                    <i class="ti {{ $theme['icon'] }}"></i>
                </div>
            </div>

            @if ($section)
                <div class="space-y-9">
                    @foreach ($section->tiers as $tier)
                    <div>
                        {{-- Tier label banner --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-1 h-px border-white/15 border-t"></div>
                            <span class="bg-linear-to-r {{ $theme['tierBanner'] }} text-white text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full whitespace-nowrap shadow-md ring-1">
                                {{ $tier->label_th }}
                            </span>
                            <div class="flex-1 h-px border-white/15 border-t"></div>
                        </div>

                        {{-- Member grid: same photo + name-plate card as About Us, per-division palette --}}
                        <div class="flex flex-wrap justify-center gap-3.5 sm:gap-5">
                            @foreach ($tier->members as $member)
                            <div class="w-24 sm:w-28 group">
                                <div class="rounded-xl p-[2.5px] bg-linear-to-b {{ $theme['cardOuter'] }} transition-transform duration-150 group-hover:-translate-y-0.5">
                                    <div class="rounded-[10px] overflow-hidden {{ $theme['cardInnerBg'] }}">
                                        <div class="w-full aspect-square flex items-center justify-center">
                                            @if ($member->photo_url)
                                                <img src="{{ $member->photo_url }}" alt="{{ $member->name_th }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="ti ti-user text-3xl opacity-40 text-white/60"></i>
                                            @endif
                                        </div>
                                        <div class="bg-linear-to-r {{ $theme['nameRibbon'] }} border-t text-center px-1 py-1.5">
                                            <p class="text-[10px] sm:text-[11px] font-semibold leading-tight">{{ $member->name_th }}</p>
                                            @if ($member->position_th)
                                            <p class="text-[9px] opacity-80 leading-tight">{{ $member->position_th }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-white/60 text-sm py-10">No organization data yet for this division.</p>
            @endif
        </div>
    </div>
</div>
@endsection
