@extends('public.layouts.app')
@section('title', $article->getLocalizedTitle())
@section('meta_description', $article->getLocalizedExcerpt())
@if ($article->thumbnail_url) @section('og_image', $article->thumbnail_url) @endif

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col lg:flex-row gap-10">

        {{-- Article body --}}
        <article class="flex-1 min-w-0">
            {{-- Breadcrumb --}}
            <nav class="text-xs text-slate-500 mb-4" aria-label="breadcrumb">
                <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('public.news.index') }}" class="hover:text-blue-700">{{ __('nav.news') }}</a>
                <span class="mx-1.5">/</span>
                <span class="text-slate-700">{{ Str::limit($article->getLocalizedTitle(), 40) }}</span>
            </nav>

            {{-- Category + Date --}}
            <div class="flex items-center gap-3 mb-3 flex-wrap">
                @if ($article->category)
                <span class="text-xs font-medium text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
                    {{ $article->category->getLocalizedName() }}
                </span>
                @endif
                <span class="text-xs text-slate-400">
                    <i class="ti ti-calendar mr-1"></i>
                    {{ $article->published_at?->format('d F Y') }}
                </span>
                <span class="text-xs text-slate-400">
                    <i class="ti ti-eye mr-1"></i>
                    {{ number_format($article->view_count) }} {{ __('article.views') }}
                </span>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl lg:text-3xl font-bold text-slate-800 leading-tight mb-4">
                {{ $article->getLocalizedTitle() }}
            </h1>

            {{-- Thumbnail --}}
            @if ($article->thumbnail_url)
            <div class="rounded-2xl overflow-hidden mb-6 aspect-video bg-slate-100">
                <img src="{{ $article->thumbnail_url }}"
                     alt="{{ $article->getLocalizedTitle() }}"
                     class="w-full h-full object-cover">
            </div>
            @endif

            {{-- Excerpt --}}
            @if ($article->getLocalizedExcerpt())
            <p class="text-lg text-slate-600 leading-relaxed mb-6 border-l-4 border-blue-400 pl-4 italic">
                {{ $article->getLocalizedExcerpt() }}
            </p>
            @endif

            {{-- Article body --}}
            <div class="prose prose-slate prose-sm sm:prose-base max-w-none
                        prose-headings:font-bold prose-headings:text-slate-800
                        prose-a:text-blue-700 prose-a:no-underline hover:prose-a:underline
                        prose-img:rounded-xl prose-img:shadow-sm">
                {!! $article->getLocalizedBody() !!}
            </div>

            {{-- Attachments --}}
            @if ($article->attachments->count())
            <div class="mt-8 p-5 bg-slate-50 rounded-2xl border border-slate-200">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">
                    <i class="ti ti-paperclip mr-1.5"></i>{{ __('article.attachments') }}
                </h3>
                <ul class="space-y-2">
                    @foreach ($article->attachments as $att)
                    <li>
                        <a href="{{ $att->url }}" download
                           class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200
                                  hover:border-blue-300 hover:shadow-sm transition-all group">
                            <i class="ti ti-file text-blue-600 text-lg"></i>
                            <span class="flex-1 text-sm text-slate-700 group-hover:text-blue-700 truncate">
                                {{ $att->original_name }}
                            </span>
                            <span class="text-xs text-slate-400 shrink-0">{{ $att->formatted_size }}</span>
                            <i class="ti ti-download text-slate-400 group-hover:text-blue-600"></i>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Tags --}}
            @if ($article->tags->count())
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($article->tags as $tag)
                <a href="{{ route('public.news.index', ['tag' => $tag->slug]) }}"
                   class="text-xs px-3 py-1.5 bg-slate-100 hover:bg-blue-50 text-slate-600
                          hover:text-blue-700 rounded-full transition-colors">
                    #{{ $tag->name }}
                </a>
                @endforeach
            </div>
            @endif
        </article>

        {{-- Sidebar: related articles --}}
        @if ($related->count())
        <aside class="lg:w-72 shrink-0">
            <div class="sticky top-20">
                <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">
                    {{ __('article.related') }}
                </h2>
                <div class="space-y-3">
                    @foreach ($related as $item)
                        @include('components.public.article-card', ['article' => $item, 'variant' => 'compact'])
                    @endforeach
                </div>
            </div>
        </aside>
        @endif

    </div>
</div>
@endsection
