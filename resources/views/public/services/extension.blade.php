@extends('public.layouts.app')
@section('title', __('services.extension.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <nav class="text-xs text-slate-500 mb-5" aria-label="{{ __('layout.breadcrumb') }}">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-blue-700">{{ __('nav.services') }}</a>
        <span class="mx-1.5">/</span>
        <span>{{ __('services.extension.title') }}</span>
    </nav>

    <div class="bg-gradient-to-br from-green-700 to-green-600 text-white rounded-3xl p-8 mb-8">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="ti ti-refresh text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold">{{ __('services.extension.title') }}</h1>
        </div>
        <p class="text-green-100 leading-relaxed">{{ __('services.extension.description') }}</p>
    </div>

    {{-- Extension types --}}
    <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('services.extension.types') }}</h2>
    <div class="grid sm:grid-cols-2 gap-3 mb-8">
        @foreach ([
            'tourist', 'retirement', 'education', 'business', 'family', 'medical'
        ] as $type)
        <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-slate-200">
            <i class="ti ti-check text-green-600 shrink-0"></i>
            <span class="text-sm text-slate-700">{{ __("extension.type.{$type}") }}</span>
        </div>
        @endforeach
    </div>

    {{-- Required documents --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-6">
        <h2 class="font-semibold text-slate-800 flex items-center gap-2 mb-3">
            <i class="ti ti-files text-blue-600"></i>{{ __('services.extension.required_docs') }}
        </h2>
        <ul class="space-y-1.5 text-sm text-slate-700">
            @foreach (['passport', 'photo', 'tm7_form', 'fee', 'supporting'] as $doc)
            <li class="flex items-start gap-2">
                <i class="ti ti-point-filled text-blue-400 mt-1 shrink-0 text-xs"></i>
                {{ __("extension.doc.{$doc}") }}
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Forms --}}
    @if ($forms->count())
    <h2 class="text-lg font-semibold text-slate-800 mb-3">{{ __('services.forms') }}</h2>
    <div class="space-y-2 mb-6">
        @foreach ($forms as $form)
        @cmsEdit($form)<div class="flex items-center gap-4 p-3 bg-white rounded-xl border border-slate-200">
            <i class="ti ti-file-type-pdf text-red-600 text-xl shrink-0"></i>
            <div class="flex-1"><p class="text-sm font-medium text-slate-800">{{ $form->getLocalizedName() }}</p></div>
            <a href="{{ route('public.downloads.download', $form->id) }}"
               class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200
                      px-3 py-1.5 rounded-lg hover:bg-blue-100" download>
                <i class="ti ti-download mr-1"></i>{{ __('services.pdf') }}
            </a>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
