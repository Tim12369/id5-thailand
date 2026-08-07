@extends('public.layouts.app')
@section('title', 'FAQ — ' . __('nav.faq'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="breadcrumb">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>FAQ</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('faq.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('faq.subtitle') }}</p>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('public.faq') }}" class="mb-8">
        <div class="relative">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
            <input type="search" name="q" value="{{ $search }}"
                   placeholder="{{ __('faq.search_placeholder') }}"
                   class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 bg-white
                          focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100
                          text-sm placeholder-slate-400 transition-all">
            @if ($search)
            <a href="{{ route('public.faq') }}"
               class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600
                      p-1 rounded-lg hover:bg-slate-100">
                <i class="ti ti-x text-sm"></i>
            </a>
            @endif
        </div>
        @if ($search)
        <p class="text-xs text-slate-500 mt-2">
            {{ __('faq.search_results_for') }} "<strong>{{ $search }}</strong>"
        </p>
        @endif
    </form>

    {{-- FAQ grouped by category (Alpine.js accordions) --}}
    @forelse ($grouped as $category)
    <div class="mb-8">
        <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800 mb-3">
            @if ($category->icon)
                <i class="ti {{ $category->icon }} text-blue-600"></i>
            @endif
            {{ $category->getLocalizedName() }}
        </h2>

        <div class="space-y-2">
            @foreach ($category->faqs as $faq)
            <div x-data="{ open: false }"
                 class="bg-white rounded-2xl border border-slate-200 overflow-hidden
                        hover:border-blue-200 transition-colors">

                <button @click="open = !open"
                        :aria-expanded="open"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                    <span class="text-sm font-medium text-slate-800">
                        {{ $faq->getLocalizedQuestion() }}
                    </span>
                    <i class="ti ti-chevron-down shrink-0 text-slate-400 transition-transform duration-200"
                       :class="{ 'rotate-180': open }"></i>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-cloak
                     class="border-t border-slate-100 px-5 py-4">
                    <div class="prose prose-sm prose-slate max-w-none prose-a:text-blue-700">
                        {!! $faq->getLocalizedAnswer() !!}
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="text-center py-20">
        <i class="ti ti-help-off text-5xl text-slate-300 mb-3 block"></i>
        <p class="text-slate-500 text-sm">{{ __('faq.no_results') }}</p>
        @if ($search)
        <a href="{{ route('public.faq') }}" class="text-sm text-blue-700 mt-2 inline-block">
            {{ __('faq.clear_search') }}
        </a>
        @endif
    </div>
    @endforelse

</div>
@endsection
