@extends('public.layouts.app')
@section('title', __("personas.{$persona}") . ' — ' . __("topics.{$topic}"))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 mb-5" aria-label="{{ __('layout.breadcrumb') }}">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.smart-guide.index') }}" class="hover:text-blue-700">{{ __('nav.smart_guide_short') }}</a>
        <span class="mx-1.5">/</span>
        <span>{{ __("personas.{$persona}") }}</span>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-1">
            @if ($topicMeta?->icon)
            <div class="w-10 h-10 bg-{{ $topicMeta->color ?? 'blue' }}-50 rounded-xl flex items-center justify-center shrink-0">
                <i class="ti {{ $topicMeta->icon }} text-{{ $topicMeta->color ?? 'blue' }}-600"></i>
            </div>
            @endif
            <h1 class="text-2xl font-bold text-slate-800">
                {{ __("personas.{$persona}") }} — {{ __("topics.{$topic}") }}
            </h1>
        </div>
        <p class="text-slate-500 text-sm">{{ __('smart_guide.sw1h_hint') }}</p>
    </div>

    {{-- Deadline warning banner --}}
    @php $isThai = app()->getLocale() === 'th'; @endphp
    @if ($topicMeta?->is_urgent)
    <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <i class="ti ti-alert-triangle text-red-600 text-xl shrink-0 mt-0.5"></i>
        <p class="text-sm text-red-800 font-medium">
            {{ __('smart_guide.deadline_warning') }}:
            {{ $topicMeta->deadline_days === 1 ? __('smart_guide.deadline_24_hours') : $topicMeta->deadline_days . ' ' . __('smart_guide.deadline_days_label') }}
        </p>
    </div>
    @elseif ($topicMeta?->deadline_days)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <i class="ti ti-clock text-amber-600 text-xl shrink-0 mt-0.5"></i>
        <p class="text-sm text-amber-800 font-medium">
            {{ __('smart_guide.deadline_warning') }}: {{ __('smart_guide.every_n_days', ['count' => $topicMeta->deadline_days]) }}
        </p>
    </div>
    @endif

    {{-- Topic switcher --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($availableTopics as $t)
        <a href="{{ route('public.smart-guide.show', [$persona, $t]) }}"
           class="px-4 py-1.5 rounded-full text-sm font-medium border transition-colors
                  {{ $t === $topic
                     ? 'bg-blue-700 text-white border-blue-700'
                     : 'bg-white text-slate-600 border-slate-200 hover:border-blue-300 hover:text-blue-700' }}">
            {{ __("topics.{$t}") }}
        </a>
        @endforeach
    </div>

    {{-- SW1H Tabs (Alpine.js) --}}
    <div x-data="{ activeTab: '{{ array_key_first(array_filter($dimensions)) ?? 'who' }}' }">

        {{-- Tab buttons --}}
        <div class="flex gap-1 bg-slate-100 rounded-2xl p-1 mb-6 flex-wrap">
            @foreach ($allDimensions as $dim)
            @if (isset($dimensions[$dim]))
            <button @click="activeTab = '{{ $dim }}'"
                    :class="activeTab === '{{ $dim }}'
                            ? 'bg-white text-blue-700 shadow-sm font-semibold'
                            : 'text-slate-500 hover:text-slate-800'"
                    class="flex-1 px-3 py-2.5 rounded-xl text-sm transition-all duration-150 capitalize min-w-0">
                {{ __("sw1h.{$dim}") }}
            </button>
            @endif
            @endforeach
        </div>

        {{-- Tab content panels --}}
        @foreach ($allDimensions as $dim)
        @if (isset($dimensions[$dim]))
        @cmsEdit($dimensions[$dim])
        <div x-show="activeTab === '{{ $dim }}'"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-white rounded-2xl border border-slate-200 p-6 lg:p-8 space-y-6">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <span class="text-lg font-bold text-blue-700">{{ strtoupper(substr($dim, 0, 1)) }}</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">{{ __("sw1h.{$dim}") }}</h2>
                    <p class="text-xs text-slate-500">{{ __("sw1h.{$dim}_desc") }}</p>
                </div>
            </div>

            <div class="prose prose-slate prose-sm sm:prose-base max-w-none
                        prose-headings:font-semibold prose-a:text-blue-700">
                {!! $dimensions[$dim]->getLocalizedContent() !!}
            </div>

            {{-- WHAT: structured documents checklist --}}
            @if ($dim === 'what' && $structured['documents']->isNotEmpty())
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ __('smart_guide.documents_checklist') }}</h3>
                <div class="space-y-2">
                    @foreach ($structured['documents'] as $doc)
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                        <i class="ti ti-square-rounded-check text-{{ $doc->is_required ? 'green' : 'slate' }}-500 text-lg shrink-0"></i>
                        <span class="flex-1 text-sm text-slate-700">{{ $doc->getLocalizedName() }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                     {{ $doc->is_required ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $doc->is_required ? __('smart_guide.required') : __('smart_guide.optional') }}
                        </span>
                        @if ($doc->form)
                        <a href="{{ route('public.downloads.download', $doc->form->id) }}"
                           class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg hover:bg-blue-100 transition-colors" download>
                            <i class="ti ti-download mr-1"></i>{{ __('smart_guide.download_form') }}
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- HOW: structured steps --}}
            @if ($dim === 'how' && $structured['steps']->isNotEmpty())
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ __('smart_guide.steps_title') }}</h3>
                <div class="space-y-2">
                    @foreach ($structured['steps'] as $step)
                    <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                        <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                            {{ $step->step_number }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $step->getLocalizedTitle() }}</p>
                            @if ($step->getLocalizedDescription())
                            <p class="text-xs text-slate-500 mt-0.5">{{ $step->getLocalizedDescription() }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- WHERE: structured locations --}}
            @if ($dim === 'where' && $structured['locations']->isNotEmpty())
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ __('smart_guide.locations_title') }}</h3>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach ($structured['locations'] as $loc)
                    <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                        <i class="ti {{ $loc->type === 'online' ? 'ti-device-laptop' : ($loc->type === 'embassy' ? 'ti-building-bank' : ($loc->type === 'office' ? 'ti-building' : 'ti-map-pin')) }}
                              text-blue-600 text-lg shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800">{{ $loc->getLocalizedLabel() }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('smart_guide.location_' . $loc->type) }}</p>
                            <div class="flex flex-wrap gap-2 mt-1.5">
                                @if ($loc->url)
                                <a href="{{ $loc->url }}" target="_blank" rel="noopener"
                                   class="text-xs font-medium text-blue-700 hover:text-blue-800">
                                    {{ __('smart_guide.visit_website') }} <i class="ti ti-external-link"></i>
                                </a>
                                @endif
                                @if ($loc->station)
                                <a href="{{ route('public.offices.show', $loc->station) }}"
                                   class="text-xs font-medium text-blue-700 hover:text-blue-800">
                                    {{ __('smart_guide.view_on_map') }} <i class="ti ti-map-pin"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
        @endforeach

        {{-- No content fallback --}}
        @if (collect($dimensions)->filter()->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <i class="ti ti-mood-empty text-5xl mb-3 block"></i>
            <p class="text-sm">{{ __('smart_guide.no_content') }}</p>
        </div>
        @endif

    </div>

    {{-- Change persona --}}
    <div class="mt-8 text-center">
        <p class="text-sm text-slate-500 mb-2">{{ __('smart_guide.wrong_persona') }}</p>
        <a href="{{ route('public.smart-guide.index') }}"
           class="text-sm font-medium text-blue-700 hover:text-blue-800">
            ← {{ __('smart_guide.change_persona') }}
        </a>
    </div>
</div>
@endsection
