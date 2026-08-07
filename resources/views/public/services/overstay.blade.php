@extends('public.layouts.app')
@section('title', __('services.overstay.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <nav class="text-xs text-slate-500 mb-5" aria-label="breadcrumb">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('nav.home') }}</a>
        <span class="mx-1.5">/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-blue-700">{{ __('nav.services') }}</a>
        <span class="mx-1.5">/</span>
        <span>{{ __('services.overstay.title') }}</span>
    </nav>

    {{-- Urgent warning banner --}}
    <div class="bg-red-600 text-white rounded-3xl p-6 mb-8 flex items-start gap-4">
        <i class="ti ti-alert-circle text-4xl shrink-0"></i>
        <div>
            <h1 class="text-xl font-bold mb-1">{{ __('services.overstay.title') }}</h1>
            <p class="text-red-200 text-sm leading-relaxed">{{ __('services.overstay.warning') }}</p>
        </div>
    </div>

    {{-- Penalties table --}}
    <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('services.overstay.penalties') }}</h2>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-8">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">{{ __('overstay.period') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">{{ __('overstay.fine') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">{{ __('overstay.consequence') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ([
                    ['period' => '1–89 days',     'fine' => '500 THB/day (max 20,000)',     'consequence' => __('overstay.consequence_short')],
                    ['period' => '90 days–1 year', 'fine' => 'Max 20,000 THB + deportation', 'consequence' => __('overstay.consequence_medium')],
                    ['period' => '1–3 years',      'fine' => 'Deportation',                  'consequence' => __('overstay.consequence_long')],
                    ['period' => '3–5 years',      'fine' => 'Deportation',                  'consequence' => __('overstay.consequence_vlong')],
                    ['period' => '> 5 years',      'fine' => 'Deportation',                  'consequence' => __('overstay.consequence_vvlong')],
                ] as $row)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $row['period'] }}</td>
                    <td class="px-4 py-3 text-red-700">{{ $row['fine'] }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row['consequence'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Self-surrender info --}}
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 mb-6">
        <h2 class="font-semibold text-green-900 flex items-center gap-2 mb-2">
            <i class="ti ti-door-enter text-green-600"></i>{{ __('services.overstay.self_surrender') }}
        </h2>
        <p class="text-sm text-green-800">{{ __('services.overstay.self_surrender_desc') }}</p>
    </div>

    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('public.contact') }}"
           class="inline-flex items-center gap-2 bg-blue-700 hover:bg-blue-800 text-white
                  text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
            <i class="ti ti-map-pin"></i>{{ __('contact.title') }}
        </a>
    </div>
</div>
@endsection
