@extends('public.layouts.app')
@section('title', __('downloads.title'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <nav class="text-xs text-slate-500 mb-2" aria-label="breadcrumb">
            <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ __('nav.downloads') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('downloads.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('downloads.subtitle') }}</p>
    </div>

    {{-- Info note --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-8">
        <i class="ti ti-info-circle text-blue-600 text-lg shrink-0 mt-0.5"></i>
        <p class="text-sm text-blue-800">{{ __('downloads.pdf_note') }}</p>
    </div>

    @forelse ($forms->keys() as $cat)
    @if ($forms[$cat]->count())
    <section class="mb-8">
        <h2 class="text-base font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <i class="ti ti-folder text-blue-500"></i>
            {{ $categories[$cat] ?? ucfirst($cat) }}
        </h2>

        <div class="grid sm:grid-cols-2 gap-3">
            @foreach ($forms[$cat] as $form)
            <div class="flex items-center gap-4 bg-white rounded-2xl border border-slate-200
                        hover:border-blue-300 hover:shadow-sm transition-all p-4 group">
                {{-- Icon --}}
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-file-type-pdf text-red-600 text-2xl"></i>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                            {{ $form->code }}
                        </span>
                        <span class="text-xs text-slate-400">v{{ $form->version }}</span>
                    </div>
                    <p class="text-sm font-medium text-slate-800 truncate">
                        {{ $form->getLocalizedName() }}
                    </p>
                    @if ($form->getLocalizedDescription())
                    <p class="text-xs text-slate-400 truncate mt-0.5">
                        {{ $form->getLocalizedDescription() }}
                    </p>
                    @endif
                </div>

                {{-- Download button --}}
                <a href="{{ route('public.downloads.download', $form->id) }}"
                   class="shrink-0 inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700
                          text-white text-xs font-medium px-3 py-2 rounded-xl transition-colors"
                   title="{{ __('downloads.download') }} {{ $form->code }}"
                   download>
                    <i class="ti ti-download text-sm"></i>
                    <span class="hidden sm:inline">{{ $form->formatted_size }}</span>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif
    @empty
    <div class="text-center py-16 text-slate-400">
        <i class="ti ti-folder-off text-5xl mb-3 block"></i>
        <p class="text-sm">{{ __('downloads.no_forms') }}</p>
    </div>
    @endforelse

</div>
@endsection
