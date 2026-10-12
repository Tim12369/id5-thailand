@extends('public.layouts.app')
@section('title', __('assessment.result.title'))

@section('content')
@php
    $status = $result->status;
    $rec = $result->recommendation;
    $isThai = app()->getLocale() === 'th';

    $riskLevel = $rec?->risk_level ?? 'info';
    $riskStyles = [
        'info'   => 'bg-slate-100 text-slate-600 border-slate-200',
        'low'    => 'bg-green-50 text-green-700 border-green-200',
        'medium' => 'bg-amber-50 text-amber-700 border-amber-200',
        'high'   => 'bg-red-50 text-red-700 border-red-200',
    ];
    $riskStyle = $riskStyles[$riskLevel] ?? $riskStyles['info'];

    // Admin-authored content (nullable until officers fill it in)
    $legalBasis   = $rec?->localized('legal_basis');
    $validity     = $rec?->localized('validity_note');
    $agency       = $rec?->localized('responsible_agency');
    $notices      = $rec?->localized('important_notices');
    $deadlineText = $rec?->localized('deadlines');
    $allowed      = $rec ? $rec->localizedList('allowed_activities') : [];
    $restricted   = $rec ? $rec->localizedList('restricted_activities') : [];

    // Real structured guidance reused from the existing Smart Guide
    $meta      = $guide['meta'] ?? null;
    $documents = $guide['structured']['documents'] ?? collect();
    $locations = $guide['structured']['locations'] ?? collect();

    $confidence = $result->confidence;
    $confBar = $confidence >= 70 ? 'bg-green-500' : ($confidence >= 40 ? 'bg-amber-500' : 'bg-slate-400');

    $hasDbContent = $legalBasis || $validity || $agency || $notices || $deadlineText || $allowed || $restricted;

    $isUndetermined = $result->isUndetermined();
    $isHighRisk = ! $isUndetermined && ($riskLevel === 'high' || $status->key === 'emergency');
@endphp

<div x-data="{}" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 mb-6 no-print" aria-label="{{ __('layout.breadcrumb') }}">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.assessment.start') }}" class="hover:text-blue-700">{{ __('assessment.title') }}</a>
        <span class="mx-1.5">/</span>
        <span>{{ __('assessment.result.eyebrow') }}</span>
    </nav>

    {{-- Status hero --}}
    @cmsEdit($rec)
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-4">
        <div class="bg-linear-to-br from-blue-700 via-blue-800 to-blue-900 p-6 sm:p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-200 mb-3">{{ __('assessment.result.your_status') }}</p>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center shrink-0 ring-1 ring-white/20">
                    <i class="ti {{ $status->icon }} text-3xl"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold leading-tight">{{ $status->localizedLabel() }}</h1>
                    @unless ($isUndetermined)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium mt-2 px-2.5 py-1 rounded-full border {{ $riskStyle }}">
                        <i class="ti ti-activity-heartbeat"></i>
                        {{ __('assessment.result.risk') }}: {{ __('assessment.risk.' . $riskLevel) }}
                    </span>
                    @endunless
                </div>
            </div>

            {{-- Confidence meter (skipped for the "undetermined" outcome — a 0%
                 bar next to a status name would read as broken, not honest) --}}
            @unless ($isUndetermined)
            <div class="mt-6">
                <div class="flex items-center justify-between text-xs text-blue-200 mb-1.5">
                    <span>{{ __('assessment.result.profile_match') }}</span>
                    <span class="font-semibold text-white">{{ $confidence }}%</span>
                </div>
                <div class="h-2 rounded-full bg-white/15 overflow-hidden" role="progressbar" aria-valuenow="{{ $confidence }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full {{ $confBar }}" style="width: {{ max(4, $confidence) }}%"></div>
                </div>
            </div>
            @else
            <p class="mt-6 text-sm text-blue-100 leading-relaxed">{{ __('assessment.result.undetermined_body') }}</p>
            @endunless
        </div>
    </div>

    {{-- Mandatory disclaimer --}}
    <x-public.assessment.disclaimer :prominent="true" class="mb-6" />

    {{-- Emergency Information — always the first thing shown for high-risk
         outcomes (detention, overstay, awaiting removal), never buried below
         the fold. --}}
    @if ($isHighRisk)
        <div class="rounded-2xl border-2 border-red-300 bg-red-50 p-5 mb-6 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-600 text-white flex items-center justify-center shrink-0">
                <i class="ti ti-urgent text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-sm font-bold text-red-900">{{ __('assessment.result.emergency_title') }}</h2>
                <p class="text-sm text-red-800 leading-relaxed mt-1">{{ __('assessment.result.emergency_body') }}</p>
                <a href="tel:{{ $hotline }}" class="inline-flex items-center gap-1.5 mt-3 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                    <i class="ti ti-phone"></i> {{ __('assessment.result.emergency_call') }}
                </a>
            </div>
        </div>
    @endif

    {{-- Legal basis (admin content) --}}
    @if ($legalBasis || $validity)
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            @if ($legalBasis)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="ti ti-scale text-blue-600"></i> {{ __('assessment.result.legal_basis') }}</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $legalBasis }}</p>
                </div>
            @endif
            @if ($validity)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="ti ti-calendar text-blue-600"></i> {{ __('assessment.result.validity') }}</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $validity }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Allowed / Restricted (admin content) --}}
    @if ($allowed || $restricted)
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            @if ($allowed)
                <div class="rounded-2xl border border-green-200 bg-green-50/50 p-5">
                    <h2 class="text-sm font-semibold text-green-800 mb-3 flex items-center gap-2"><i class="ti ti-circle-check"></i> {{ __('assessment.result.allowed') }}</h2>
                    <ul class="space-y-1.5">
                        @foreach ($allowed as $item)
                            <li class="flex items-start gap-2 text-sm text-slate-700"><i class="ti ti-check text-green-600 shrink-0 mt-0.5"></i><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($restricted)
                <div class="rounded-2xl border border-red-200 bg-red-50/50 p-5">
                    <h2 class="text-sm font-semibold text-red-800 mb-3 flex items-center gap-2"><i class="ti ti-circle-x"></i> {{ __('assessment.result.restricted') }}</h2>
                    <ul class="space-y-1.5">
                        @foreach ($restricted as $item)
                            <li class="flex items-start gap-2 text-sm text-slate-700"><i class="ti ti-x text-red-600 shrink-0 mt-0.5"></i><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Deadline + Responsible agency (admin content, or real deadline from the guide topic) --}}
    @php
        $showDeadline = $deadlineText || ($meta && $meta->deadline_days);
    @endphp
    @if ($showDeadline || $agency)
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            @if ($showDeadline)
                <div class="rounded-2xl border {{ $meta?->is_urgent ? 'border-red-200 bg-red-50/50' : 'border-amber-200 bg-amber-50/50' }} p-5">
                    <h2 class="text-sm font-semibold {{ $meta?->is_urgent ? 'text-red-800' : 'text-amber-800' }} mb-2 flex items-center gap-2">
                        <i class="ti ti-clock"></i> {{ __('assessment.result.deadlines') }}
                    </h2>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        @if ($deadlineText)
                            {{ $deadlineText }}
                        @elseif ($meta && $meta->deadline_days)
                            {{ $meta->deadline_days === 1 ? __('smart_guide.within_24_hours') : __('smart_guide.every_n_days', ['count' => $meta->deadline_days]) }}
                        @endif
                    </p>
                </div>
            @endif
            @if ($agency)
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2"><i class="ti ti-building-bank text-blue-600"></i> {{ __('assessment.result.agency') }}</h2>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $agency }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Important notices (admin content) --}}
    @if ($notices)
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 mb-6">
            <h2 class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2"><i class="ti ti-alert-circle"></i> {{ __('assessment.result.notices') }}</h2>
            <p class="text-sm text-blue-800 leading-relaxed">{{ $notices }}</p>
        </div>
    @endif

    {{-- Your answers recap (always available — from the user's own input) --}}
    @if (!empty($recap))
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="ti ti-list-details text-blue-600"></i> {{ __('assessment.result.your_answers') }}</h2>
            <dl class="divide-y divide-slate-100">
                @foreach ($recap as $row)
                    <div class="flex items-start justify-between gap-4 py-2">
                        <dt class="text-sm text-slate-500 flex-1">{{ $row['question'] }}</dt>
                        <dd class="text-sm font-medium text-slate-800 text-right">{{ implode(', ', $row['answers']) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    {{-- Commonly required documents (real, from the matched Smart Guide topic) --}}
    @if ($documents->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1 flex items-center gap-2"><i class="ti ti-file-description text-blue-600"></i> {{ __('assessment.result.required_documents') }}</h2>
            <p class="text-xs text-slate-400 mb-3">{{ __('assessment.result.documents_note') }}</p>
            @cmsEdit($meta)<div class="space-y-2">
                @foreach ($documents as $doc)
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                        <i class="ti ti-square-rounded-check text-{{ $doc->is_required ? 'green' : 'slate' }}-500 text-lg shrink-0"></i>
                        <span class="flex-1 text-sm text-slate-700">{{ $doc->getLocalizedName() }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $doc->is_required ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $doc->is_required ? __('smart_guide.required') : __('smart_guide.optional') }}
                        </span>
                        @if ($doc->form)
                            <a href="{{ route('public.downloads.download', $doc->form->id) }}"
                               class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg hover:bg-blue-100 transition-colors no-print" download>
                                <i class="ti ti-download mr-1"></i>{{ __('smart_guide.download_form') }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Where to go (real locations from the matched topic) --}}
    @if ($locations->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="ti ti-map-pin text-blue-600"></i> {{ __('assessment.result.where') }}</h2>
            @cmsEdit($meta)<div class="grid sm:grid-cols-2 gap-3">
                @foreach ($locations as $loc)
                    <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                        <i class="ti {{ $loc->type === 'online' ? 'ti-device-laptop' : ($loc->type === 'embassy' ? 'ti-building-bank' : ($loc->type === 'office' ? 'ti-building' : 'ti-map-pin')) }} text-blue-600 text-lg shrink-0 mt-0.5"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ $loc->getLocalizedLabel() }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('smart_guide.location_' . $loc->type) }}</p>
                            <div class="flex flex-wrap gap-2 mt-1.5 no-print">
                                @if ($loc->url)
                                    <a href="{{ $loc->url }}" target="_blank" rel="noopener" class="text-xs font-medium text-blue-700 hover:text-blue-800">{{ __('smart_guide.visit_website') }} <i class="ti ti-external-link"></i></a>
                                @endif
                                @if ($loc->station)
                                    <a href="{{ route('public.offices.show', $loc->station) }}" class="text-xs font-medium text-blue-700 hover:text-blue-800">{{ __('smart_guide.view_on_map') }} <i class="ti ti-map-pin"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Placeholder note only when there is genuinely nothing authored/structured yet --}}
    @unless ($hasDbContent || $documents->isNotEmpty() || $locations->isNotEmpty() || $isUndetermined)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 mb-6 text-sm text-slate-500 flex items-start gap-3">
            <i class="ti ti-info-circle text-slate-400 text-lg shrink-0 mt-0.5"></i>
            <p>{{ __('assessment.result.content_pending') }}</p>
        </div>
    @endunless

    {{-- Recommended next steps → existing Smart Guide deep-dive --}}
    @if ($persona && count($topics))
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="ti ti-list-check text-blue-600"></i> {{ __('assessment.result.next_steps') }}</h2>
            <div class="space-y-2">
                @foreach ($topics as $topic)
                    <a href="{{ route('public.smart-guide.show', [$persona, $topic]) }}"
                       class="focus-ring flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors group">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i class="ti ti-book"></i></span>
                        <span class="flex-1 text-sm font-medium text-slate-700 group-hover:text-blue-700">{{ __("topics.{$topic}") }}</span>
                        <i class="ti ti-arrow-right text-slate-400 group-hover:text-blue-600"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @elseif ($isUndetermined)
        {{-- No specific topic to point to — offer the general Smart Guide
             browser instead of leaving this section empty. --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><i class="ti ti-list-check text-blue-600"></i> {{ __('assessment.result.next_steps') }}</h2>
            <a href="{{ route('public.smart-guide.index') }}"
               class="focus-ring flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors group">
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i class="ti ti-compass"></i></span>
                <span class="flex-1 text-sm font-medium text-slate-700 group-hover:text-blue-700">{{ __('smart_guide.change_persona') }}</span>
                <i class="ti ti-arrow-right text-slate-400 group-hover:text-blue-600"></i>
            </a>
        </div>
    @endif

    {{-- Alternatives (low-confidence / "you might also be") --}}
    @if (!empty($result->alternatives))
        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6 no-print">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">{{ __('assessment.result.also_consider') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($result->alternatives as $alt)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full bg-slate-100 text-slate-600">
                        <i class="ti {{ $alt->icon }}"></i> {{ $alt->localizedLabel() }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3 no-print">
        <a href="{{ route('public.assessment.start') }}"
           class="focus-ring inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="ti ti-refresh"></i> {{ __('assessment.result.restart') }}
        </a>
        <button type="button" @click="window.print()"
                class="focus-ring inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="ti ti-printer"></i> {{ __('assessment.result.print') }}
        </button>
        <a href="{{ route('public.offices.index') }}"
           class="focus-ring inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
            <i class="ti ti-map-pin"></i> {{ __('assessment.result.find_office') }}
        </a>
        <a href="{{ route('public.contact') }}"
           class="focus-ring inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-sm font-semibold text-white transition-colors">
            <i class="ti ti-headset"></i> {{ __('assessment.result.ask_officer') }}
        </a>
        <a href="{{ route('public.emergency') }}"
           class="focus-ring inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-sm font-semibold text-white transition-colors">
            <i class="ti ti-urgent"></i> {{ __('assessment.emergency_button') }}
        </a>
    </div>
</div>
@endsection
