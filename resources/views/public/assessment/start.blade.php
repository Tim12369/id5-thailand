@extends('public.layouts.app')
@section('title', __('assessment.title'))
@section('meta_description', __('assessment.subtitle'))

@section('content')
@php
    $byStage = collect($tree)->groupBy('stage');
    $stageNames = collect(range(1, $stageCount))->mapWithKeys(fn ($s) => [$s => __('assessment.stages.' . $s)]);
@endphp

<div class="bg-linear-to-b from-slate-50 to-white"
     x-data="assessment(@js($tree), {{ $stageCount }}, @js($stageNames))" x-init="init()">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

        {{-- Breadcrumb --}}
        <nav class="text-xs text-slate-500 mb-6" aria-label="breadcrumb">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('assessment.title') }}</span>
        </nav>

        {{-- Header --}}
        <div class="text-center mb-8">
            <span class="section-eyebrow justify-center">{{ __('assessment.eyebrow') }}</span>
            <h1 class="section-title mt-2">{{ __('assessment.title') }}</h1>
            <p class="section-subtitle mx-auto">{{ __('assessment.subtitle') }}</p>
        </div>

        {{-- Progress --}}
        <div class="mb-8">
            <x-public.assessment.progress-steps :stage-count="$stageCount" />
        </div>
        <p class="sr-only" aria-live="polite"
           x-text="'{{ __('assessment.stage_of', ['current' => '__C__', 'total' => '__T__']) }}'.replace('__C__', progressIndex + 1).replace('__T__', applicableStages.length)"></p>

        {{-- Visible "progress saved" indicator (auto-save is otherwise silent) --}}
        <div x-show="justSaved" x-transition.opacity x-cloak
             class="flex items-center gap-1.5 justify-center text-xs text-green-700 mb-4" role="status" aria-live="polite">
            <i class="ti ti-cloud-check"></i> {{ __('assessment.saved_indicator') }}
        </div>

        {{-- Hidden form used to POST the collected answers for server-side inference --}}
        <form method="POST" action="{{ route('public.assessment.result') }}" x-ref="resultForm" class="hidden">
            @csrf
            <input type="hidden" name="answers" x-ref="answersField">
        </form>

        {{-- Stages --}}
        <div x-show="!reviewing">
            @foreach ($byStage as $stage => $nodes)
                <section x-show="currentStage === {{ $stage }}" x-cloak
                         x-transition:enter="transition ease-out duration-250"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         aria-labelledby="stage-{{ $stage }}-heading">
                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-5 sm:p-8">
                        <h2 id="stage-{{ $stage }}-heading" tabindex="-1" class="text-lg font-bold text-slate-800 mb-6 outline-none">
                            <span class="text-blue-600">{{ $stage }}.</span> {{ __('assessment.stages.' . $stage) }}
                        </h2>

                        @if ($stage == 1)
                            {{-- Privacy notice, shown before collecting anything about the
                                 person a representative is acting for. --}}
                            <div x-show="answers.who_are_you === 'representative'" x-cloak
                                 x-transition
                                 class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 mb-6"
                                 role="note">
                                <i class="ti ti-shield-lock text-blue-600 text-lg shrink-0 mt-0.5" aria-hidden="true"></i>
                                <div>
                                    <p class="text-sm font-semibold text-blue-900">{{ __('assessment.privacy_title') }}</p>
                                    <p class="text-xs text-blue-700 mt-0.5 leading-relaxed">{{ __('assessment.privacy_body') }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-8">
                            @foreach ($nodes as $node)
                                <x-public.assessment.question :node="$node" />
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach

            {{-- Navigation --}}
            <x-public.assessment.wizard-nav />
        </div>

        {{-- Review screen — shown after "View My Results" on the last stage,
             before anything is submitted, so the user can check or change
             any answer first. --}}
        <div x-show="reviewing" x-cloak
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-5 sm:p-8">
                <h2 id="review-heading" tabindex="-1" class="text-lg font-bold text-slate-800 mb-6 outline-none">
                    {{ __('assessment.result.review_title') }}
                </h2>

                <dl class="divide-y divide-slate-100 mb-6">
                    <template x-for="row in reviewRecap" :key="row.question">
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="flex-1 min-w-0">
                                <dt class="text-xs text-slate-400" x-text="row.question"></dt>
                                <dd class="text-sm font-medium text-slate-800 mt-0.5" x-text="row.answers.join(', ')"></dd>
                            </div>
                            <button type="button" @click="editStage(row.stage)"
                                    class="focus-ring shrink-0 text-xs font-medium text-blue-700 hover:text-blue-800 px-2 py-1 rounded-lg hover:bg-blue-50">
                                <i class="ti ti-edit mr-1"></i>{{ __('assessment.back') }}
                            </button>
                        </div>
                    </template>
                </dl>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="backToEdit()"
                            class="focus-ring inline-flex items-center gap-1.5 px-5 py-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                        <i class="ti ti-arrow-left"></i> {{ __('assessment.back') }}
                    </button>
                    <button type="button" @click="submit()"
                            class="focus-ring inline-flex items-center gap-1.5 px-6 py-3 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        {{ __('assessment.view_results') }} <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mandatory disclaimer --}}
        <div class="mt-8">
            <x-public.assessment.disclaimer />
        </div>

        {{-- Always-reachable emergency exit from the wizard itself, not just
             the sitewide header. --}}
        <div class="text-center mt-4">
            <a href="{{ route('public.emergency') }}"
               class="focus-ring inline-flex items-center gap-1.5 text-sm font-medium text-red-600 hover:text-red-700">
                <i class="ti ti-urgent"></i> {{ __('assessment.emergency_button') }}
            </a>
        </div>
    </div>
</div>
@endsection
