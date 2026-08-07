@extends('public.layouts.app')
@section('title', 'ITA — Integrity & Transparency Assessment')

@php
    // Thai fiscal year badge (พ.ศ.), matching the convention already used on
    // the About Us org chart letterheads elsewhere on the public site.
    $fiscalYearBe = now()->month >= 10 ? now()->year + 1 + 543 : now()->year + 543;
@endphp

@section('content')
<div class="bg-linear-to-br from-blue-950 via-blue-900 to-blue-800 relative overflow-hidden">
    <div class="absolute inset-0 pattern-dots opacity-20"></div>
    <div class="absolute inset-x-0 top-0 h-64 pointer-events-none bg-[radial-gradient(ellipse_60%_60%_at_50%_0%,_rgba(251,191,36,0.16),transparent_70%)]"></div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12">
        <nav class="text-xs text-blue-300 mb-6" aria-label="Breadcrumb">
            <a href="{{ route('public.home') }}" class="focus-ring hover:text-white transition-colors">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span class="text-blue-200">ITA</span>
        </nav>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="section-eyebrow text-amber-300">
                    <i class="ti ti-shield-check"></i> ITA
                </span>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mt-2 tracking-tight text-balance">
                    Integrity &amp; Transparency Assessment
                </h1>
                <p class="text-blue-200 text-sm mt-1.5 max-w-lg leading-relaxed">
                    Assessment results and transparency documents, published for public scrutiny.
                </p>
            </div>
            <span class="inline-flex shrink-0 items-center gap-1.5 bg-white/10 text-amber-200 border border-amber-300/30 rounded-full px-4 py-1.5 text-sm font-bold">
                <i class="ti ti-calendar-event text-xs"></i> {{ $fiscalYearBe }}
            </span>
        </div>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="space-y-12">
        @forelse ($sections as $section)
            @php $sectionHeadingId = 'ita-section-' . $section->id; @endphp
            <section aria-labelledby="{{ $sectionHeadingId }}">
                <div class="bg-linear-to-r from-slate-800 via-slate-700 to-slate-800 text-white rounded-xl px-5 py-3.5 mb-5 flex items-center gap-3 shadow-[0_8px_20px_-8px_rgba(15,23,42,0.4)]">
                    <span class="shrink-0 w-8 h-8 rounded-lg bg-amber-400/90 text-slate-900 flex items-center justify-center text-xs font-extrabold" aria-hidden="true">
                        {{ $section->section_number }}
                    </span>
                    <h2 id="{{ $sectionHeadingId }}" class="font-semibold tracking-tight">{{ $section->getLocalizedTitle() }}</h2>
                </div>

                @if ($section->getLocalizedDescription())
                    <p class="text-sm text-slate-500 mb-5 -mt-2 px-1 leading-relaxed">{{ $section->getLocalizedDescription() }}</p>
                @endif

                <div class="grid sm:grid-cols-2 gap-5">
                    @foreach ($section->cards as $card)
                        @php
                            $color = $card->color ?? '#4F46E5';
                            $panelId = 'ita-card-panel-' . $card->id;
                        @endphp
                        <div x-data="{ open: false }"
                             class="group bg-white rounded-2xl border border-slate-200 overflow-hidden transition-all duration-200 hover:shadow-[0_12px_28px_-10px_rgba(15,23,42,0.18)] hover:-translate-y-0.5"
                             :class="open ? 'shadow-[0_12px_28px_-10px_rgba(15,23,42,0.18)] -translate-y-0.5 border-slate-300' : ''">
                            <button type="button" @click="open = !open"
                                    class="focus-ring w-full flex items-center gap-4 p-4 text-left"
                                    :aria-expanded="open.toString()"
                                    aria-controls="{{ $panelId }}">
                                <span class="shrink-0 w-14 h-14 rounded-xl flex items-center justify-center text-lg font-extrabold text-white shadow-inner relative overflow-hidden transition-transform duration-200 group-hover:scale-[1.04]"
                                      style="background: linear-gradient(135deg, {{ $color }}, color-mix(in srgb, {{ $color }} 70%, black));">
                                    <span class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.35),transparent_60%)]"></span>
                                    <span class="relative">{{ $card->card_number }}</span>
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-center gap-1.5 font-semibold text-slate-800 text-sm leading-snug">
                                        @if ($card->icon)
                                            <i class="ti ti-{{ $card->icon }} text-base shrink-0" style="color: {{ $color }}" aria-hidden="true"></i>
                                        @endif
                                        {{ $card->getLocalizedTitle() }}
                                    </span>
                                </span>
                                <i class="ti text-slate-300 shrink-0 transition-transform duration-200 group-hover:text-slate-400" :class="open ? 'ti-chevron-up' : 'ti-chevron-down'" aria-hidden="true"></i>
                            </button>

                            {{-- Height-animated reveal (grid-rows trick — no JS plugin needed) --}}
                            <div class="grid transition-[grid-template-rows] duration-300 ease-out"
                                 :style="{ gridTemplateRows: open ? '1fr' : '0fr' }">
                                <div class="overflow-hidden">
                                    <div id="{{ $panelId }}" class="px-4 pb-4 text-sm text-slate-600 leading-relaxed border-t"
                                         style="border-color: color-mix(in srgb, {{ $color }} 20%, white);">
                                        <div class="pt-3 pl-18">{{ $card->getLocalizedContent() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
                <i class="ti ti-shield-search text-3xl text-slate-300 mb-3 block"></i>
                <p class="text-sm text-slate-400">No ITA information has been published yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
