@extends('public.layouts.app')
@section('title', __('smart_guide.search_results_for') . ' "' . $query . '"')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <nav class="text-xs text-slate-500 mb-5" aria-label="{{ __('layout.breadcrumb') }}">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.smart-guide.index') }}" class="hover:text-blue-700">{{ __('nav.smart_guide_short') }}</a>
        <span class="mx-1.5">/</span>
        <span>{{ __('smart_guide.search_button') }}</span>
    </nav>

    <h1 class="text-xl font-bold text-slate-800 mb-1">
        {{ __('smart_guide.search_results_for') }} "{{ $query }}"
    </h1>

    @if (count($matches) === 0)
        <div class="text-center py-16 text-slate-400">
            <i class="ti ti-mood-empty text-5xl mb-3 block"></i>
            <p class="text-sm mb-6">{{ __('smart_guide.search_no_results') }}</p>
            <a href="{{ route('public.smart-guide.index') }}"
               class="text-sm font-medium text-blue-700 hover:text-blue-800">
                ← {{ __('smart_guide.change_persona') }}
            </a>
        </div>
    @else
        <p class="text-sm text-slate-500 mb-6">{{ __('smart_guide.search_pick_one') }}</p>
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach ($matches as $topic)
            <a href="{{ route('public.smart-guide.show', [$persona, $topic]) }}"
               class="flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl
                      hover:border-blue-300 hover:shadow-md transition-all">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="ti ti-file-text text-blue-600"></i>
                </div>
                <span class="text-sm font-medium text-slate-800">{{ __("topics.{$topic}") }}</span>
            </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
