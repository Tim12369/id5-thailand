@extends('public.layouts.app')
@section('title', __('search.title') . ($query ? ': ' . $query : ''))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-bold text-slate-800 mb-6">{{ __('search.title') }}</h1>

    {{-- Search bar --}}
    <form method="GET" action="{{ route('public.search') }}" class="mb-8">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="search" name="q" value="{{ $query }}"
                       autofocus
                       placeholder="{{ __('search.placeholder') }}"
                       class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 bg-white
                              focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100
                              text-sm placeholder-slate-400">
            </div>
            <button type="submit"
                    class="px-5 py-3 bg-blue-700 hover:bg-blue-800 text-white text-sm
                           font-medium rounded-2xl transition-colors">
                {{ __('search.search_btn') }}
            </button>
        </div>
    </form>

    @if ($results === null)
    {{-- No query yet --}}
    <div class="text-center py-16 text-slate-400">
        <i class="ti ti-search text-5xl mb-3 block"></i>
        <p class="text-sm">{{ __('search.enter_query') }}</p>
    </div>

    @elseif ($results['total'] === 0)
    <div class="text-center py-16 text-slate-400">
        <i class="ti ti-mood-empty text-5xl mb-3 block"></i>
        <p class="font-medium text-slate-600 mb-1">{{ __('search.no_results') }}</p>
        <p class="text-sm">{{ __('search.try_different') }}</p>
    </div>

    @else
    <p class="text-sm text-slate-500 mb-6">
        {{ __('search.found', ['count' => $results['total'], 'query' => $query]) }}
    </p>

    {{-- Articles --}}
    @if ($results['articles']->count())
    <section class="mb-8">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
            {{ __('search.articles') }} ({{ $results['articles']->count() }})
        </h2>
        <div class="space-y-3">
            @foreach ($results['articles'] as $article)
                @include('components.public.article-card', ['article' => $article])
            @endforeach
        </div>
    </section>
    @endif

    {{-- FAQs --}}
    @if ($results['faqs']->count())
    <section class="mb-8">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
            {{ __('nav.faq_short') }} ({{ $results['faqs']->count() }})
        </h2>
        <div class="space-y-2">
            @foreach ($results['faqs'] as $faq)
            @cmsEdit($faq)<a href="{{ route('public.faq') }}#faq-{{ $faq->id }}"
               class="block p-4 bg-white rounded-xl border border-slate-200
                      hover:border-blue-300 hover:shadow-sm transition-all group">
                <p class="text-sm font-medium text-slate-800 group-hover:text-blue-700 mb-1">
                    {{ $faq->getLocalizedQuestion() }}
                </p>
                <p class="text-xs text-slate-500 line-clamp-2">
                    {{ strip_tags($faq->getLocalizedAnswer()) }}
                </p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Smart Guides --}}
    @if ($results['guides']->count())
    <section>
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
            {{ __('nav.smart_guide_short') }} ({{ $results['guides']->count() }})
        </h2>
        <div class="space-y-2">
            @foreach ($results['guides'] as $guide)
            @cmsEdit($guide)<a href="{{ route('public.smart-guide.show', [$guide->persona, $guide->topic]) }}"
               class="block p-4 bg-white rounded-xl border border-slate-200
                      hover:border-blue-300 hover:shadow-sm transition-all group">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">
                        {{ __("personas.{$guide->persona}") }}
                    </span>
                    <span class="text-xs text-slate-400">→ {{ __("topics.{$guide->topic}") }}</span>
                </div>
                <p class="text-sm text-slate-600 line-clamp-2">
                    {{ strip_tags($guide->getLocalizedContent()) }}
                </p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    @endif

</div>
@endsection
