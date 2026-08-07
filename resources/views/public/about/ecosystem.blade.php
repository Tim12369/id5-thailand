@extends('public.layouts.app')
@section('title', __('about.ecosystem'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="text-center mb-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">{{ __('about.ecosystem') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('about.ecosystem_subtitle') }}</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
        $ecosystem = [
            ['slug' => 'move',    'icon' => 'ti-plane',        'color' => 'blue',   'services' => ['visa', 'entry', 'exit']],
            ['slug' => 'stay',    'icon' => 'ti-home',         'color' => 'teal',   'services' => ['visa_status', 'extension', 'address']],
            ['slug' => 'work',    'icon' => 'ti-briefcase',    'color' => 'indigo', 'services' => ['work_permit', 'tm30', 'employer']],
            ['slug' => 'study',   'icon' => 'ti-school',       'color' => 'purple', 'services' => ['student_visa', '90day', 'university']],
            ['slug' => 'invest',  'icon' => 'ti-chart-line',   'color' => 'amber',  'services' => ['boi', 'investment_visa', 'smart_visa']],
            ['slug' => 'family',  'icon' => 'ti-heart',        'color' => 'pink',   'services' => ['dependent_visa', 'extension', 'report']],
            ['slug' => 'special', 'icon' => 'ti-star',         'color' => 'green',  'services' => ['ltr', 'digital_nomad', 'elite']],
        ];
        @endphp

        @foreach ($ecosystem as $item)
        <div class="bg-white rounded-2xl border border-slate-200 p-5
                    hover:border-{{ $item['color'] }}-300 hover:shadow-md transition-all group">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-{{ $item['color'] }}-50 rounded-xl flex items-center justify-center
                            group-hover:bg-{{ $item['color'] }}-100 transition-colors">
                    <i class="ti {{ $item['icon'] }} text-{{ $item['color'] }}-600 text-xl"></i>
                </div>
                <h3 class="font-semibold text-slate-800">{{ __("ecosystem.{$item['slug']}.title") }}</h3>
            </div>
            <p class="text-xs text-slate-500 mb-3 leading-relaxed">
                {{ __("ecosystem.{$item['slug']}.desc") }}
            </p>
            <ul class="space-y-1">
                @foreach ($item['services'] as $svc)
                <li class="text-xs text-{{ $item['color'] }}-700 flex items-center gap-1.5">
                    <i class="ti ti-point-filled text-{{ $item['color'] }}-400 text-[8px]"></i>
                    {{ __("ecosystem.service.{$svc}") }}
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
</div>
@endsection
