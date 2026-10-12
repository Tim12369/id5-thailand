@extends('public.layouts.app')
@section('title', __('about.title'))

@php
    // Thai fiscal year (BE = Gregorian + 543, fiscal cycle Oct–Sep), rendered in Thai numerals
    // to match real government letterhead style (e.g. ๒๕๖๙ rather than 2569).
    $now = now();
    $fiscalYearBe = $now->month >= 10 ? $now->year + 1 + 543 : $now->year + 543;
    $toThaiNumerals = fn (int $n) => str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'],
        (string) $n
    );
    $fiscalYearThai = $toThaiNumerals($fiscalYearBe);

    // Per-organization letterhead style — each real chart (ตร./สตม./บก.ตม.5) uses its own
    // distinct layout and palette, modeled separately rather than one shared theme.
    $letterheads = [
        'police' => [
            'panel' => 'bg-[radial-gradient(ellipse_at_top,_#7f1d1d,_#450a0a_55%,_#1c0505_100%)] border-amber-400/70',
            'pattern' => true,
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,_rgba(251,191,36,0.22),transparent_70%)]',
            'tierBorder' => 'border-amber-400/40',
            'tierBanner' => 'bg-linear-to-r from-amber-600 via-yellow-300 to-amber-600 text-red-950 ring-1 ring-amber-200/80',
            'yearText' => 'text-amber-200',
            'cardOuter' => 'bg-linear-to-b from-amber-300 via-amber-500 to-amber-700 shadow-[0_8px_20px_-6px_rgba(0,0,0,0.6)]',
            'cardInnerBg' => 'bg-gradient-to-b from-amber-50 via-white to-amber-50',
            'nameRibbon' => 'bg-linear-to-r from-blue-950 via-blue-900 to-blue-950 text-amber-200 border-amber-400/80',
            'iconColor' => 'text-slate-500',
            'corner' => 'text-amber-300/50',
        ],
        'immigration_bureau' => [
            'panel' => 'bg-[radial-gradient(ellipse_at_top,_#ffffff,_#eef2f9_50%,_#dde8f7_100%)] border-amber-300/70',
            'pattern' => 'hatch-blue',
            'glow' => 'bg-[radial-gradient(ellipse_65%_45%_at_50%_0%,_rgba(251,191,36,0.16),transparent_45%),radial-gradient(ellipse_70%_40%_at_50%_5%,_rgba(37,99,235,0.14),transparent_70%)]',
            'tierBorder' => 'border-blue-200',
            'tierBanner' => 'bg-linear-to-r from-blue-700 via-blue-500 to-blue-700 text-white ring-1 ring-amber-300/70 shadow-[0_4px_14px_-3px_rgba(37,99,235,0.5)]',
            'yearText' => 'text-blue-700',
            'cardOuter' => 'bg-linear-to-b from-amber-300 via-blue-500 to-blue-800 shadow-[0_10px_22px_-6px_rgba(30,58,138,0.5)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,_#1e3a8a,_#0b1538_100%)]',
            'nameRibbon' => 'bg-white text-blue-900 border-amber-400',
            'iconColor' => 'text-white',
            'corner' => 'text-amber-500/70',
        ],
        'division5' => [
            'panel' => 'bg-[radial-gradient(ellipse_at_top,_#1e293b,_#0a0f1d_55%,_#000_100%)] border-amber-400/60',
            'pattern' => 'dots',
            'glow' => 'bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,_rgba(251,191,36,0.18),transparent_70%)]',
            'tierBorder' => 'border-amber-400/30',
            'tierBanner' => 'bg-linear-to-r from-amber-600 via-yellow-300 to-amber-600 text-slate-950 ring-1 ring-amber-200/80',
            'yearText' => 'text-amber-300',
            'cardOuter' => 'bg-linear-to-b from-amber-400 via-amber-600 to-amber-800 shadow-[0_0_18px_-2px_rgba(251,191,36,0.35)]',
            'cardInnerBg' => 'bg-[radial-gradient(ellipse_at_top,_#1e293b,_#0a0f1d_100%)]',
            'nameRibbon' => 'bg-linear-to-r from-slate-900 via-slate-800 to-slate-900 text-amber-200 border-amber-400/70',
            'iconColor' => 'text-amber-200/60',
            'corner' => 'text-amber-300/40',
        ],
    ];
    $defaultStyle = $letterheads['division5'];
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ activeSection: '{{ $sections->first()?->slug }}' }">

    <nav class="text-xs text-slate-500 flex items-center gap-1.5 mb-6">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span>/</span>
        <span>{{ __('about.title') }}</span>
    </nav>

    <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">{{ __('about.title') }}</h1>
        <p class="text-slate-500 text-sm mt-1">{{ __('about.org_chart_subtitle') }}</p>
    </div>

    {{-- ── About ID5 & Immigration Division 5 ───────────────────────── --}}
    <div class="grid sm:grid-cols-2 gap-5 mb-10">
        <div class="card p-6">
            <div class="icon-tile w-11 h-11 bg-blue-50 text-blue-700 mb-4">
                <i class="ti ti-sparkles text-xl"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800 mb-2">{{ __('about.what_is') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ __('about.description') }}</p>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">{{ __('about.mission') }}</p>
            <p class="text-sm text-slate-600 leading-relaxed">{{ __('about.mission_desc') }}</p>
        </div>

        <div class="card p-6">
            <div class="icon-tile w-11 h-11 bg-amber-50 text-amber-600 mb-4">
                <i class="ti ti-shield-check text-xl"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800 mb-2">{{ __('about.division5_title') }}</h2>
            <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ __('about.division5_desc') }}</p>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">{{ __('about.division5_coverage') }}</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach (__('about.division5_provinces') as $province)
                <span class="badge badge-info">{{ $province }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Section Tabs ─────────────────────────────────────────────── --}}
    <div class="flex bg-slate-100 rounded-2xl p-1.5 mb-6 shadow-inner gap-1">
        @foreach ($sections as $section)
        @cmsEdit($section)<button type="button" @click="activeSection = '{{ $section->slug }}'"
                :class="activeSection === '{{ $section->slug }}'
                    ? 'bg-linear-to-r from-blue-700 via-blue-600 to-blue-700 text-white shadow-md'
                    : 'bg-transparent text-slate-500 hover:bg-white hover:text-slate-700'"
                class="flex-1 py-3.5 rounded-xl text-sm font-semibold transition-all duration-200">
            {{ $section->name_th }}
        </button>
        @endforeach
    </div>

    {{-- ── Letterhead Chart Panels (one bespoke style per organization) ── --}}
    @foreach ($sections as $section)
    @php $s = $letterheads[$section->slug] ?? $defaultStyle; @endphp
    @cmsEdit($section)<div x-show="activeSection === '{{ $section->slug }}'"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-[0.99]" x-transition:enter-end="opacity-100 scale-100">
        <div class="relative overflow-hidden rounded-3xl border-2 {{ $s['panel'] }} p-5 sm:p-9 shadow-2xl ring-1 ring-black/10">

            @if ($s['pattern'] === true)
            {{-- Diagonal diamond texture (ตร. letterhead) --}}
            <div class="absolute inset-0 pointer-events-none opacity-70"
                 style="background-image:
                    repeating-linear-gradient(45deg, rgba(255,255,255,.04) 0px, rgba(255,255,255,.04) 1px, transparent 1px, transparent 14px),
                    repeating-linear-gradient(-45deg, rgba(255,255,255,.04) 0px, rgba(255,255,255,.04) 1px, transparent 1px, transparent 14px);">
            </div>
            @elseif ($s['pattern'] === 'dots')
            {{-- Fine dot-grid texture (บก.ตม.5 letterhead) --}}
            <div class="absolute inset-0 pointer-events-none opacity-50"
                 style="background-image: radial-gradient(rgba(251,191,36,.18) 1px, transparent 1px); background-size: 18px 18px;">
            </div>
            @elseif ($s['pattern'] === 'hatch-blue')
            {{-- Fine diagonal hairline texture in navy/gold, subtle on the white สตม. letterhead --}}
            <div class="absolute inset-0 pointer-events-none opacity-60"
                 style="background-image:
                    repeating-linear-gradient(45deg, rgba(30,58,138,.035) 0px, rgba(30,58,138,.035) 1px, transparent 1px, transparent 16px),
                    repeating-linear-gradient(-45deg, rgba(251,191,36,.05) 0px, rgba(251,191,36,.05) 1px, transparent 1px, transparent 16px);">
            </div>
            @endif

            {{-- Spotlight glow behind the top banner --}}
            <div class="absolute inset-x-0 top-0 h-72 pointer-events-none {{ $s['glow'] }}"></div>

            {{-- Ornate corner flourishes --}}
            <span class="absolute top-3 left-3 {{ $s['corner'] }} text-lg leading-none select-none pointer-events-none">◆</span>
            <span class="absolute top-3 right-3 {{ $s['corner'] }} text-lg leading-none select-none pointer-events-none">◆</span>
            <span class="absolute bottom-3 left-3 {{ $s['corner'] }} text-lg leading-none select-none pointer-events-none opacity-80">◆</span>
            <span class="absolute bottom-3 right-3 {{ $s['corner'] }} text-lg leading-none select-none pointer-events-none opacity-80">◆</span>

            <div class="relative">

            {{-- ── Top Letterhead Banner (bespoke per organization) ──────── --}}
            @if ($section->slug === 'police')
                <div class="text-center mb-9">
                    <div class="inline-flex items-center gap-3 bg-linear-to-r from-amber-600 via-yellow-300 to-amber-600 text-red-950 rounded-full px-6 sm:px-10 py-3 font-bold text-sm sm:text-lg shadow-[0_6px_20px_-4px_rgba(0,0,0,0.5)] border-2 border-amber-200 ring-2 ring-amber-100/40">
                        <span class="text-red-800 drop-shadow-sm">◆</span>
                        <span class="drop-shadow-sm">{{ __('about.senior_command') }} {{ $section->name_th }}</span>
                        <span class="text-red-800 drop-shadow-sm">◆</span>
                    </div>
                    <p class="{{ $s['yearText'] }} text-xs sm:text-sm mt-3.5 font-medium tracking-wide">
                        {{ __('about.fiscal_year_be') }} {{ $fiscalYearThai }}
                    </p>
                </div>
            @elseif ($section->slug === 'immigration_bureau')
                <div class="rounded-2xl overflow-hidden border-2 border-amber-300/70 mb-9 shadow-[0_14px_30px_-10px_rgba(30,58,138,0.35)] ring-1 ring-blue-200/60">
                    <div class="relative bg-linear-to-r from-slate-50 via-white to-slate-50 px-5 sm:px-8 py-5 flex items-center gap-4 border-b-[3px] border-amber-400">
                        <div class="relative w-14 h-14 rounded-full bg-linear-to-br from-blue-700 via-blue-800 to-blue-950 flex items-center justify-center shrink-0 shadow-[0_4px_14px_-2px_rgba(30,58,138,0.6)] ring-2 ring-amber-300">
                            <span class="absolute inset-0 rounded-full bg-[radial-gradient(circle_at_35%_25%,rgba(255,255,255,0.35),transparent_55%)]"></span>
                            <i class="ti ti-shield-check text-amber-300 text-2xl relative"></i>
                        </div>
                        <div>
                            <p class="text-blue-900 font-bold text-base sm:text-2xl leading-tight">{{ $section->name_th }}</p>
                            @if ($section->name_en)
                            <p class="text-blue-700 text-xs sm:text-sm uppercase tracking-widest mt-0.5">{{ $section->name_en }}</p>
                            @endif
                            <div class="h-[2px] w-14 bg-linear-to-r from-amber-400 to-transparent mt-1.5"></div>
                        </div>
                        <div class="ml-auto relative">
                            <div class="bg-linear-to-br from-amber-500 via-yellow-400 to-amber-600 text-blue-950 text-[11px] sm:text-xs font-bold rounded-lg px-4 py-2 text-center shrink-0 shadow-[0_4px_12px_-2px_rgba(217,119,6,0.55)] ring-1 ring-amber-200">
                                {{ __('about.fiscal_year') }}<br><span class="text-sm sm:text-base">{{ $fiscalYearBe }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative bg-linear-to-r from-blue-800 via-blue-600 to-blue-800 text-white text-center py-3.5 overflow-hidden">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_100%_at_50%_0%,rgba(251,191,36,0.25),transparent_70%)]"></div>
                        <p class="relative font-bold text-sm sm:text-lg drop-shadow-sm">{{ __('about.senior_command') }}</p>
                        <p class="relative text-[11px] sm:text-xs uppercase tracking-[0.2em] opacity-90 mt-0.5">{{ __('about.org_chart') }}</p>
                    </div>
                </div>
            @else
                <div class="rounded-2xl overflow-hidden mb-6 shadow-[0_6px_20px_-4px_rgba(251,191,36,0.25)] ring-1 ring-amber-300/30">
                    <div class="bg-linear-to-r from-amber-600 via-yellow-300 to-amber-600 text-slate-950 px-5 sm:px-8 py-3.5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-950 flex items-center justify-center shrink-0 ring-2 ring-amber-200/60">
                            <i class="ti ti-shield-check text-amber-300 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm sm:text-lg leading-tight drop-shadow-sm">{{ $section->name_th }}</p>
                            @if ($section->name_en)
                            <p class="text-[11px] sm:text-xs uppercase tracking-widest opacity-80">{{ $section->name_en }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <p class="text-center {{ $s['yearText'] }} text-xs sm:text-sm font-medium tracking-wide mb-9">
                    {{ __('about.command_chart') }} {{ $fiscalYearThai }}
                </p>
            @endif

            <div class="space-y-9">
                @foreach ($section->tiers as $tier)
                <div>
                    {{-- Tier label banner --}}
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-1 h-px {{ $s['tierBorder'] }} border-t"></div>
                        @cmsEdit($tier->members->first(), 'ชื่อแถวในผัง', $tier->label_th)<span class="{{ $s['tierBanner'] }} text-xs sm:text-sm font-semibold px-4 py-1.5 rounded-full whitespace-nowrap shadow-md">
                            {{ $tier->label_th }}
                        </span>
                        <div class="flex-1 h-px {{ $s['tierBorder'] }} border-t"></div>
                    </div>

                    {{-- Member grid: each person is a self-contained letterhead "card" (photo + name ribbon) --}}
                    <div class="flex flex-wrap justify-center gap-3.5 sm:gap-5">
                        @foreach ($tier->members as $member)
                        @cmsEdit($member)
                        <a href="https://division5.immigration.go.th/" target="_blank" rel="noopener"
                           class="focus-ring w-24 sm:w-28 group block">
                            <div class="rounded-xl p-[2.5px] {{ $s['cardOuter'] }} transition-transform duration-150 group-hover:-translate-y-0.5">
                                <div class="rounded-[10px] overflow-hidden {{ $s['cardInnerBg'] }}">
                                    <div class="w-full aspect-square flex items-center justify-center">
                                        @if ($member->photo_url)
                                            <img src="{{ $member->photo_url }}" alt="{{ $member->name_th }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="ti ti-user text-3xl opacity-40 {{ $s['iconColor'] }}"></i>
                                        @endif
                                    </div>
                                    <div class="{{ $s['nameRibbon'] }} border-t text-center px-1 py-1.5">
                                        <p class="text-[10px] sm:text-[11px] font-semibold leading-tight">{{ $member->name_th }}</p>
                                        @if ($member->rank_th)
                                        <p class="text-[9px] opacity-80 leading-tight">{{ $member->rank_th }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    @cmsAdd(\App\Support\CmsEdit::newPersonIn($section, $tier), 'เพิ่มบุคลากรในแถวนี้', $tier->label_th)</div>
                </div>
                @endforeach

                @if ($section->tiers->isEmpty())
                <p class="text-center text-slate-400 text-sm py-10">{{ __('about.no_section_data') }}</p>
                @endif
            </div>
            </div>
        </div>
    </div>
    @endforeach

    @if ($sections->isEmpty())
    <div class="text-center py-16 text-slate-400">
        <i class="ti ti-sitemap text-4xl block mb-3 opacity-50"></i>
        {{ __('about.no_chart_data') }}
    </div>
    @endif

    {{-- ── Documents & Forms ────────────────────────────────────────── --}}
    <div class="card p-6 mt-10">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="flex items-start gap-3">
                <div class="icon-tile w-11 h-11 bg-red-50 text-red-600 shrink-0">
                    <i class="ti ti-file-type-pdf text-xl"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-800">{{ __('about.documents_title') }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('about.documents_desc') }}</p>
                </div>
            </div>
            <a href="{{ route('public.downloads.index') }}" class="hidden sm:inline-flex shrink-0 text-xs font-medium text-blue-700 hover:text-blue-800 items-center gap-1">
                {{ __('about.documents_view_all') }} <i class="ti ti-arrow-right text-sm"></i>
            </a>
        </div>

        @if ($documents->isEmpty())
        <p class="text-sm text-slate-400 text-center py-6">{{ __('about.documents_empty') }}</p>
        @else
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach ($documents as $document)
            @cmsEdit($document)
            <div class="flex items-center gap-4 bg-white rounded-2xl border border-slate-200
                        hover:border-blue-300 hover:shadow-sm transition-all p-4 group">
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-file-type-pdf text-red-600 text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $document->getLocalizedName() }}</p>
                    @if ($document->getLocalizedDescription())
                    <p class="text-xs text-slate-400 truncate mt-0.5">{{ $document->getLocalizedDescription() }}</p>
                    @endif
                </div>
                <a href="{{ route('public.downloads.download', $document->id) }}"
                   class="shrink-0 inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700
                          text-white text-xs font-medium px-3 py-2 rounded-xl transition-colors"
                   title="{{ __('downloads.download') }} {{ $document->getLocalizedName() }}"
                   download>
                    <i class="ti ti-download text-sm"></i>
                    <span class="hidden sm:inline">{{ $document->formatted_size }}</span>
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
