@extends('public.layouts.app')

@section('title', __('home.title'))
@section('meta_description', __('home.hero_subtitle'))

@section('content')

{{-- ══════════════════════════════════════════════════════════════
     Hero — photo banner + identity panel
     Image is admin-editable via Internal Portal → Site Settings
     (falls back to the bundled default photo if unset).
     ══════════════════════════════════════════════════════════════ --}}
@php
    $heroImagePath = \App\Models\Setting::get('site.hero_image');
    $heroImageUrl = $heroImagePath ? asset('storage/' . $heroImagePath) : asset('images/hero-temple.jpg');

    // Hero text is admin-editable via Internal Portal > Website Management >
    // Homepage (App\Http\Controllers\Internal\Website\HomepageController),
    // stored per-locale as Setting keys, falling back to the lang file
    // defaults when unset -- same fallback pattern as the hero image above.
    $localeSuffix = app()->getLocale() === 'en' ? 'en' : 'th';
    $heroTitle = \App\Models\Setting::get("home.hero_title_{$localeSuffix}") ?: __('home.hero_title');
    $heroSubtitle = \App\Models\Setting::get("home.hero_subtitle_{$localeSuffix}") ?: __('home.hero_subtitle');
    $heroCta = \App\Models\Setting::get("home.hero_cta_{$localeSuffix}") ?: __('home.hero_cta');
@endphp
<section class="relative bg-linear-to-br from-blue-950 via-blue-900 to-blue-800 text-white overflow-hidden">
    <div class="hero-photo" style="background-image: url('{{ $heroImageUrl }}')"></div>
    <div class="absolute inset-0 bg-linear-to-r from-blue-950/90 via-blue-950/55 to-blue-900/15"></div>
    <div class="absolute inset-0 pattern-dots opacity-30"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 lg:pt-20 pb-14 lg:pb-28">
        <div class="max-w-3xl">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-[1.15] mb-5 tracking-tight">
                {!! $heroTitle !!}
            </h1>
            <p class="text-blue-200 text-base sm:text-lg leading-relaxed mb-8 max-w-2xl">
                {!! $heroSubtitle !!}
            </p>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('public.about') }}" class="btn-on-dark btn-lg">
                    {{ $heroCta }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Action cards — overlap the hero/content boundary on larger screens --}}
<div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6 lg:py-0 lg:absolute lg:-top-10 lg:left-4 lg:right-4 xl:left-8 xl:right-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
            <x-public.action-card :href="route('public.services.visa')" icon="ti-id" color="blue"
                :title="__('home.actions.visa.title')" :desc="__('home.actions.visa.desc')" />
            <x-public.action-card :href="route('public.services.tm30')" icon="ti-file-text" color="teal"
                :title="__('home.actions.tm30.title')" :desc="__('home.actions.tm30.desc')" />
            <x-public.action-card :href="route('public.services.90day')" icon="ti-calendar-event" color="indigo"
                :title="__('home.actions.90day.title')" :desc="__('home.actions.90day.desc')" />
            <x-public.action-card :href="route('public.emergency')" icon="ti-phone" color="red"
                :title="__('home.actions.emergency.title')" :desc="__('home.actions.emergency.desc')" />
        </div>
    </div>
</div>

{{-- Spacer that accounts for the overlapping action cards on desktop --}}
<div class="hidden lg:block h-16 bg-slate-50"></div>

{{-- ══════════════════════════════════════════════════════════════
     Explore ID5 Services — 6-card division/feature grid.
     "Who Are You?" and "Where Are You?" don't navigate anywhere new:
     "Who Are You?" smooth-scrolls to the existing persona section
     further down this page, and "Where Are You?" links straight to
     the existing office locator — neither duplicates any existing
     feature. All 6 cards are admin-editable (photo + text) via Internal
     Portal > Website Management > Homepage Divisions —
     see App\Models\HomeDivisionCard.
     ══════════════════════════════════════════════════════════════ --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
    <div class="text-center mb-9 max-w-2xl mx-auto">
        <span class="section-eyebrow justify-center">{{ __('home.divisions_eyebrow') }}</span>
        <h2 class="section-title mt-2">{{ __('home.divisions_title') }}</h2>
        <p class="section-subtitle mx-auto">{{ __('home.divisions_subtitle') }}</p>
    </div>

    @php
        // Icon/gradient/link-target per card are cosmetic/functional, not
        // admin-editable content, so they stay here rather than in the
        // database. "Who Are You?" and "Where Are You?" don't navigate
        // anywhere new -- see the note above -- everything else (photo,
        // title, description) is admin-editable for all 6 cards via
        // Internal Portal > Website Management > Homepage Divisions.
        $divisionStyles = [
            'who_are_you'    => ['icon' => 'ti-users', 'gradient' => 'from-amber-500 to-amber-700', 'href' => route('public.assessment.start')],
            'where_are_you'  => ['icon' => 'ti-map-pin-search', 'gradient' => 'from-blue-700 to-blue-900', 'href' => route('public.offices.index')],
            'technology'     => ['icon' => 'ti-cpu', 'gradient' => 'from-indigo-700 to-violet-900', 'href' => route('public.technology-division')],
            'administration' => ['icon' => 'ti-building-bank', 'gradient' => 'from-slate-700 to-slate-900', 'href' => route('public.administration-division')],
            'foreigner'      => ['icon' => 'ti-passport', 'gradient' => 'from-teal-600 to-teal-800', 'href' => route('public.foreigner-division')],
            'investigation'  => ['icon' => 'ti-fingerprint', 'gradient' => 'from-rose-700 to-red-900', 'href' => route('public.investigation-division')],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        @foreach (['who_are_you', 'where_are_you', 'technology', 'administration', 'foreigner', 'investigation'] as $key)
            @continue(!isset($divisionCards[$key]))
            @php $card = $divisionCards[$key]; $style = $divisionStyles[$key]; @endphp
            <x-public.division-card :href="$style['href']" :icon="$style['icon']" :gradient="$style['gradient']" :photo="$card->photo_url"
                :title="$card->getLocalizedTitle()" :desc="$card->getLocalizedDescription()" />
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     News & Announcements & Alerts — 3-column overview
     ══════════════════════════════════════════════════════════════ --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
    <div class="grid lg:grid-cols-3 gap-8">
        <x-public.news-column icon="ti-news" :title="__('home.latest_news')"
            :items="$content['latest_news']" :viewAllRoute="route('public.news.index')"
            :emptyText="__('home.no_news')" />

        <x-public.news-column icon="ti-speakerphone" :title="__('home.announcements')"
            :items="$content['announcements']" :viewAllRoute="route('public.announcements.index')"
            :emptyText="__('home.no_announcements')" />

        <x-public.news-column icon="ti-alert-triangle" :title="__('home.alerts')"
            :items="$content['alerts']" :viewAllRoute="route('public.alerts')"
            :emptyText="__('home.no_announcements')" />
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     Guided Assessment entry band. Replaces the old "Who Are You?"
     persona popup/scroll flow entirely — instead of asking users to
     self-classify, the "Start" button opens the guided wizard
     (/assessment) which infers their likely status from simple
     factual questions. The "Who Are You?" division card above links
     to the same place.
     ══════════════════════════════════════════════════════════════ --}}
<section class="bg-white border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
        <div class="relative overflow-hidden rounded-3xl bg-linear-to-br from-blue-800 via-blue-900 to-slate-900 text-white p-8 sm:p-12">
            <div class="absolute inset-0 pattern-dots opacity-20"></div>
            <div class="relative max-w-2xl">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-blue-200 mb-3">
                    <i class="ti ti-wand"></i> {{ __('assessment.eyebrow') }}
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold leading-tight mb-3">{{ __('home.who_are_you') }}</h2>
                <p class="text-blue-100 text-sm sm:text-base leading-relaxed mb-7 max-w-xl">
                    {{ __('assessment.subtitle') }}
                </p>
                <a href="{{ route('public.assessment.start') }}"
                   class="focus-ring inline-flex items-center gap-2 bg-white text-blue-800 hover:bg-blue-50 font-semibold text-sm px-6 py-3 rounded-xl transition-colors">
                    <i class="ti ti-player-play"></i> {{ __('assessment.title') }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════
     Bottom CTA cards
     ══════════════════════════════════════════════════════════════ --}}
<section class="bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
        <div class="grid sm:grid-cols-3 gap-5">
            <x-public.cta-banner-card href="tel:1178" icon="ti-phone" variant="danger"
                :title="__('home.bottom_cta.emergency.title')" :desc="__('home.bottom_cta.emergency.desc')" :cta="__('home.bottom_cta.emergency.cta')" />
            <x-public.cta-banner-card :href="route('public.contact')" icon="ti-brand-line" variant="line"
                :title="__('home.bottom_cta.line.title')" :desc="__('home.bottom_cta.line.desc')" :cta="__('home.bottom_cta.line.cta')" />
            <x-public.cta-banner-card :href="route('public.offices.index')" icon="ti-map-2" variant="primary"
                :title="__('home.bottom_cta.map.title')" :desc="__('home.bottom_cta.map.desc')" :cta="__('home.bottom_cta.map.cta')" />
        </div>
    </div>
</section>

@endsection
