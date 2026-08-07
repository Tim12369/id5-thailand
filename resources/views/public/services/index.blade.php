@extends('public.layouts.app')
@section('title', __('services.title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-10 max-w-2xl">
        <p class="section-eyebrow"><i class="ti ti-stack-2"></i> {{ __('nav.services') }}</p>
        <h1 class="section-title mt-2">{{ __('services.title') }}</h1>
        <p class="section-subtitle">{{ __('services.subtitle') }}</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ([
            ['route' => 'public.services.visa',      'icon' => 'ti-id',               'color' => 'blue',   'key' => 'services.visa'],
            ['route' => 'public.services.tm30',      'icon' => 'ti-file-text',         'color' => 'teal',   'key' => 'services.tm30'],
            ['route' => 'public.services.90day',     'icon' => 'ti-calendar-event',    'color' => 'indigo', 'key' => 'services.90day'],
            ['route' => 'public.services.extension', 'icon' => 'ti-refresh',           'color' => 'green',  'key' => 'services.extension'],
            ['route' => 'public.services.overstay',  'icon' => 'ti-alert-triangle',    'color' => 'amber',  'key' => 'services.overstay'],
            ['route' => 'public.downloads.index',    'icon' => 'ti-download',          'color' => 'slate',  'key' => 'services.download_forms'],
        ] as $svc)
        <a href="{{ route($svc['route']) }}"
           class="card card-hover group flex flex-col items-start gap-3 p-6">
            <span class="icon-tile w-12 h-12 bg-{{ $svc['color'] }}-50 text-{{ $svc['color'] }}-600 group-hover:bg-{{ $svc['color'] }}-100">
                <i class="ti {{ $svc['icon'] }} text-2xl"></i>
            </span>
            <div>
                <h3 class="font-semibold text-slate-800 group-hover:text-{{ $svc['color'] }}-700 mb-1">
                    {{ __($svc['key'] . '.title') }}
                </h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    {{ __($svc['key'] . '.description') }}
                </p>
            </div>
            <span class="mt-auto inline-flex items-center gap-1.5 text-xs font-semibold text-{{ $svc['color'] }}-600
                         group-hover:text-{{ $svc['color'] }}-700">
                {{ __('services.learn_more') }} <i class="ti ti-arrow-right text-xs"></i>
            </span>
        </a>
        @endforeach
    </div>
</div>
@endsection
