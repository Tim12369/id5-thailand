@extends('public.layouts.app')
@section('title', __('open_data.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 text-xs font-medium
                    px-3 py-1.5 rounded-full mb-3">
            <i class="ti ti-database"></i> Open Government Data
        </div>
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('open_data.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('open_data.subtitle') }}</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-8 flex items-start gap-3">
        <i class="ti ti-license text-blue-600 text-xl shrink-0 mt-0.5"></i>
        <div>
            <p class="font-semibold text-blue-900 text-sm">{{ __('open_data.license_title') }}</p>
            <p class="text-xs text-blue-700 mt-0.5">{{ __('open_data.license_desc') }}</p>
        </div>
    </div>

    {{-- Dataset cards --}}
    <div class="space-y-4">
        @foreach ([
            ['icon' => 'ti-users',        'color' => 'blue',  'key' => 'open_data.dataset.stations'],
            ['icon' => 'ti-chart-bar',    'color' => 'teal',  'key' => 'open_data.dataset.stats'],
            ['icon' => 'ti-file',         'color' => 'green', 'key' => 'open_data.dataset.forms'],
        ] as $ds)
        <div class="flex items-start gap-4 p-5 bg-white rounded-2xl border border-slate-200">
            <div class="w-12 h-12 bg-{{ $ds['color'] }}-50 rounded-xl flex items-center justify-center shrink-0">
                <i class="ti {{ $ds['icon'] }} text-{{ $ds['color'] }}-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-slate-800 text-sm mb-0.5">{{ __($ds['key'] . '.title') }}</p>
                <p class="text-xs text-slate-500 mb-3">{{ __($ds['key'] . '.desc') }}</p>
                <div class="flex gap-2">
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-green-50 text-green-700 rounded-lg border border-green-200">
                        <i class="ti ti-download text-xs"></i> JSON
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">
                        <i class="ti ti-download text-xs"></i> CSV
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-8 text-center">
        <p class="text-sm text-slate-500 mb-2">{{ __('open_data.api_hint') }}</p>
        <a href="{{ url('/api/v1/open-data/stations') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 bg-blue-50
                  border border-blue-200 px-4 py-2 rounded-xl hover:bg-blue-100 transition-colors">
            <i class="ti ti-api"></i> REST API
        </a>
    </div>
</div>
@endsection
