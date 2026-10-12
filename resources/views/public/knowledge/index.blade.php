@extends('public.layouts.app')
@section('title', __('knowledge.title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="{{ __('layout.breadcrumb') }}">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('nav.knowledge') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('knowledge.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('knowledge.subtitle') }}</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar: categories --}}
        <aside class="lg:w-56 shrink-0">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sticky top-20">
                <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                    {{ __('article.categories') }}
                </h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('public.knowledge.index') }}"
                           class="block px-3 py-2 text-sm rounded-lg transition-colors
                                  {{ !request('category') ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            {{ __('article.all') }}
                        </a>
                    </li>
                    @foreach ($categories as $cat)
                    <li>
                        @cmsEdit($cat)<a href="{{ route('public.knowledge.index', ['category' => $cat->slug]) }}"
                           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                  {{ request('category') === $cat->slug ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">
                            @if ($cat->icon)
                                <i class="ti {{ $cat->icon }} text-sm opacity-60"></i>
                            @endif
                            {{ $cat->getLocalizedName() }}
                        </a>
                    </li>
                    @endforeach
                </ul>

                <div class="border-t border-slate-100 mt-3 pt-3">
                    <a href="{{ route('public.faq') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600
                              hover:bg-slate-50 rounded-lg transition-colors">
                        <i class="ti ti-help text-sm opacity-60"></i>
                        {{ __('nav.faq_short') }}
                    </a>
                    <a href="{{ route('public.smart-guide.index') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600
                              hover:bg-slate-50 rounded-lg transition-colors">
                        <i class="ti ti-sparkles text-sm opacity-60"></i>
                        {{ __('nav.smart_guide_short') }}
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main: article grid --}}
        <main class="flex-1 min-w-0">
            @if ($articles->isEmpty())
            <div class="text-center py-20 text-slate-400">
                <i class="ti ti-books text-5xl mb-3 block"></i>
                <p class="text-sm">{{ __('article.no_results') }}</p>
            </div>
            @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ($articles as $article)
                @cmsEdit($article)
                <a href="{{ route('public.knowledge.show', $article->slug) }}"
                   class="flex flex-col bg-white rounded-2xl border border-slate-200
                          hover:border-blue-300 hover:shadow-md transition-all duration-200 group overflow-hidden">

                    @if ($article->thumbnail_url)
                    <div class="aspect-video overflow-hidden bg-slate-100">
                        <img src="{{ $article->thumbnail_url }}"
                             alt="{{ $article->getLocalizedTitle() }}"
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    @endif

                    <div class="p-4 flex-1 flex flex-col">
                        @if ($article->category)
                        <span class="text-[10px] font-medium text-blue-700 bg-blue-50 px-2 py-0.5
                                     rounded-full w-fit mb-2">
                            {{ $article->category->getLocalizedName() }}
                        </span>
                        @endif
                        <h3 class="font-semibold text-slate-800 text-sm group-hover:text-blue-700
                                   line-clamp-2 leading-snug mb-1 transition-colors">
                            {{ $article->getLocalizedTitle() }}
                        </h3>
                        @if ($article->getLocalizedExcerpt())
                        <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed flex-1">
                            {{ $article->getLocalizedExcerpt() }}
                        </p>
                        @endif
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                            <span class="text-xs text-slate-400">
                                {{ $article->published_at?->format('d M Y') }}
                            </span>
                            <span class="text-xs text-slate-400">
                                <i class="ti ti-eye mr-1"></i>{{ number_format($article->view_count) }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
