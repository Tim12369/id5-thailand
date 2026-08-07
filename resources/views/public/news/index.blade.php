@extends('public.layouts.app')
@section('title', __('nav.news'))

@php
    $tabs = [
        'news' => ['label' => __('home.latest_news'), 'icon' => 'ti-news'],
        'announcements' => ['label' => __('home.announcements'), 'icon' => 'ti-speakerphone'],
        'alerts' => ['label' => __('home.alerts'), 'icon' => 'ti-alert-triangle'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page header --}}
    <div class="mb-6">
        <nav class="text-xs text-slate-500 mb-2" aria-label="breadcrumb">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('nav.news') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800">{{ __('nav.news') }}</h1>
    </div>

    {{-- Tabs: Latest News / Announcements / Alerts --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 mb-8" role="tablist" aria-label="{{ __('nav.news') }}">
        @foreach ($tabs as $key => $meta)
            <a href="{{ route('public.news.index', ['tab' => $key]) }}"
               role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
               class="focus-ring flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors
                      {{ $tab === $key ? 'border-blue-700 text-blue-700' : 'border-transparent text-slate-500 hover:text-blue-700' }}">
                <i class="ti {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Article list for the active tab --}}
    @if ($articles->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <i class="ti ti-news-off text-3xl text-slate-300 mb-3 block"></i>
            <p class="text-slate-500 text-sm">{{ __('article.no_results') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($articles as $article)
                @include('components.public.article-card', ['article' => $article])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $articles->appends(['tab' => $tab])->links() }}
        </div>
    @endif
</div>
@endsection
