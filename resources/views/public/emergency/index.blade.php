@extends('public.layouts.app')
@section('title', __('emergency.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Emergency hero --}}
    <div class="bg-red-700 text-white rounded-3xl p-8 mb-8 text-center">
        <i class="ti ti-phone-call text-5xl mb-3 block"></i>
        <h1 class="text-3xl font-bold mb-2">{{ __('emergency.title') }}</h1>
        <p class="text-red-200 mb-4">{{ __('emergency.available_247') }}</p>
        <a href="tel:{{ $emergencyPhone }}"
           class="inline-flex items-center gap-3 bg-white text-red-700 text-2xl font-bold
                  px-8 py-4 rounded-2xl hover:bg-red-50 transition-colors">
            <i class="ti ti-phone text-2xl"></i>
            {{ $emergencyPhone }}
        </a>
    </div>

    {{-- Important contacts grid --}}
    <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('emergency.important_contacts') }}</h2>
    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        @foreach ([
            ['number' => '1178', 'label' => __('emergency.immigration_hotline'), 'icon' => 'ti-id', 'color' => 'blue'],
            ['number' => '191',  'label' => __('emergency.police'),               'icon' => 'ti-shield', 'color' => 'indigo'],
            ['number' => '1669', 'label' => __('emergency.ambulance'),            'icon' => 'ti-heartbeat', 'color' => 'red'],
            ['number' => '199',  'label' => __('emergency.fire'),                 'icon' => 'ti-flame', 'color' => 'orange'],
        ] as $contact)
        <a href="tel:{{ $contact['number'] }}"
           class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-slate-200
                  hover:border-{{ $contact['color'] }}-300 hover:shadow-sm transition-all group">
            <div class="w-12 h-12 bg-{{ $contact['color'] }}-50 rounded-xl flex items-center justify-center shrink-0
                        group-hover:bg-{{ $contact['color'] }}-100 transition-colors">
                <i class="ti {{ $contact['icon'] }} text-{{ $contact['color'] }}-600 text-xl"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-slate-800">{{ $contact['number'] }}</p>
                <p class="text-xs text-slate-500">{{ $contact['label'] }}</p>
            </div>
            <i class="ti ti-phone text-slate-300 ml-auto group-hover:text-{{ $contact['color'] }}-500"></i>
        </a>
        @endforeach
    </div>

    {{-- Office directory quick list --}}
    <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('emergency.nearest_offices') }}</h2>
    <div class="space-y-3">
        @foreach ($stations->take(6) as $station)
        <div class="flex items-center gap-4 p-4 bg-white rounded-xl border border-slate-200">
            <i class="ti ti-building text-blue-500 text-xl shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-slate-800">{{ $station->getLocalizedName() }}</p>
                <p class="text-xs text-slate-500">{{ $station->province }}</p>
            </div>
            @if ($station->phone)
            <a href="tel:{{ $station->phone }}"
               class="shrink-0 text-xs text-blue-700 font-medium bg-blue-50 border border-blue-200
                      px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">
                <i class="ti ti-phone mr-1"></i>{{ $station->phone }}
            </a>
            @endif
        </div>
        @endforeach
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('public.contact') }}" class="text-sm text-blue-700 font-medium hover:text-blue-800">
            {{ __('emergency.view_all_offices') }} →
        </a>
    </div>
</div>
@endsection
