@extends('public.layouts.app')
@section('title', __('announcements.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="breadcrumb">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('announcements.title') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('announcements.title') }}</h1>
    </div>

    <div class="space-y-3">
        @forelse ($articles as $article)
        <a href="{{ route('public.announcements.show', $article->slug) }}"
           class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-slate-200
                  hover:border-blue-300 hover:shadow-sm transition-all group">
            <div class="shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-400 mb-0.5">
                    {{ $article->published_at?->format('d F Y') }}
                </p>
                <h3 class="font-medium text-slate-800 text-sm group-hover:text-blue-700
                           line-clamp-2 transition-colors">
                    {{ $article->getLocalizedTitle() }}
                </h3>
                @if ($article->getLocalizedExcerpt())
                <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                    {{ $article->getLocalizedExcerpt() }}
                </p>
                @endif
            </div>
            <i class="ti ti-chevron-right text-slate-300 group-hover:text-blue-500
                      shrink-0 transition-colors mt-0.5"></i>
        </a>
        @empty
        <div class="text-center py-16 text-slate-400">
            <i class="ti ti-speakerphone text-5xl mb-3 block"></i>
            <p class="text-sm">{{ __('announcements.none') }}</p>
        </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $articles->links() }}</div>
</div>
@endsection
