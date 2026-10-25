@extends('public.layouts.app')
@section('title', $page->getLocalizedTitle())
@if ($page->excerpt) @section('meta_description', $page->excerpt) @endif

@section('content')
@cmsEdit($page)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="{{ __('layout.breadcrumb') }}">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ Str::limit($page->getLocalizedTitle(), 60) }}</span>
        </nav>
        <h1 class="text-2xl lg:text-3xl font-bold text-slate-800">{{ $page->getLocalizedTitle() }}</h1>
    </div>

    <div class="prose cms-blocks max-w-none">
        {!! $page->body_html !!}
    </div>
</div>
@endsection
