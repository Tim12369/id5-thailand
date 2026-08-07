@extends('public.layouts.app')
@section('title', $article->getLocalizedTitle())

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <nav class="text-xs text-slate-500 mb-5" aria-label="breadcrumb">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.announcements.index') }}" class="hover:text-blue-700">
            {{ __('announcements.title') }}
        </a>
        <span class="mx-1.5">/</span>
        <span>{{ Str::limit($article->getLocalizedTitle(), 40) }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:p-8">

        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">
                {{ __('announcements.label') }}
            </span>
            <span class="text-xs text-slate-400">
                {{ $article->published_at?->format('d F Y') }}
            </span>
        </div>

        <h1 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight mb-5">
            {{ $article->getLocalizedTitle() }}
        </h1>

        <div class="prose prose-slate prose-sm sm:prose-base max-w-none
                    prose-headings:font-bold prose-a:text-blue-700
                    prose-a:no-underline hover:prose-a:underline">
            {!! $article->getLocalizedBody() !!}
        </div>

        @if ($article->attachments->count())
        <div class="mt-6 pt-6 border-t border-slate-100">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">
                <i class="ti ti-paperclip mr-1.5"></i>{{ __('article.attachments') }}
            </h3>
            <ul class="space-y-2">
                @foreach ($article->attachments as $att)
                <li>
                    <a href="{{ $att->url }}" download
                       class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200
                              hover:border-blue-300 transition-all group">
                        <i class="ti ti-file text-blue-600 text-lg"></i>
                        <span class="flex-1 text-sm text-slate-700 truncate">{{ $att->original_name }}</span>
                        <i class="ti ti-download text-slate-400 group-hover:text-blue-600"></i>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="mt-6 pt-4 border-t border-slate-100">
            <a href="{{ route('public.announcements.index') }}"
               class="inline-flex items-center gap-2 text-sm text-blue-700 hover:text-blue-800 font-medium">
                <i class="ti ti-arrow-left text-sm"></i>
                {{ __('announcements.back') }}
            </a>
        </div>
    </div>
</div>
@endsection
