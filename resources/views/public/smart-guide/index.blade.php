@extends('public.layouts.app')
@section('title', __('smart_guide.title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-medium
                    px-3 py-1.5 rounded-full mb-3">
            <i class="ti ti-sparkles"></i> {{ __('smart_guide.badge') }}
        </div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ __('smart_guide.title') }}</h1>
        <p class="text-slate-500 max-w-xl mx-auto">{{ __('smart_guide.subtitle') }}</p>
    </div>

    {{-- Direct search (Option B) --}}
    <form action="{{ route('public.smart-guide.search') }}" method="GET" class="max-w-xl mx-auto mb-10">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 text-center">
            {{ __('smart_guide.or_search') }}
        </label>
        <div class="relative">
            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="q" placeholder="{{ __('smart_guide.search_placeholder') }}"
                   class="w-full pl-11 pr-24 py-3.5 rounded-2xl border border-slate-200 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            <button type="submit"
                    class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-blue-700 text-white text-sm
                           font-medium rounded-xl hover:bg-blue-800 transition-colors">
                {{ __('smart_guide.search_button') }}
            </button>
        </div>
    </form>

    {{-- Quick actions --}}
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        <a href="{{ route('public.offices.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl
                  text-sm font-medium text-slate-700 hover:border-blue-300 hover:text-blue-700 transition-colors">
            <i class="ti ti-map-pin text-blue-600"></i> {{ __('smart_guide.find_office') }}
        </a>
        <a href="{{ route('public.smart-guide.show', ['general', 'visa']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl
                  text-sm font-medium text-slate-700 hover:border-blue-300 hover:text-blue-700 transition-colors">
            <i class="ti ti-checklist text-blue-600"></i> {{ __('smart_guide.check_documents') }}
        </a>
    </div>

    {{-- Guided flow (Option A) --}}
    <div x-data="{ selectedPersona: null, topicsByPersona: {{ json_encode($topicsByPersona) }} }">

        {{-- Step indicator --}}
        <div class="text-center mb-5">
            <span x-show="!selectedPersona" class="text-xs font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
                {{ __('smart_guide.step1_label') }}
            </span>
            <span x-show="selectedPersona" x-cloak class="text-xs font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
                {{ __('smart_guide.step2_label') }}
            </span>
        </div>

        {{-- Step 1: Who are you? --}}
        <div x-show="!selectedPersona">
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 text-center">
                {{ __('smart_guide.select_persona') }}
            </h2>

            @php
            $personaIcons = [
                'tourist'      => ['icon' => 'ti-beach',      'color' => 'blue',   'bg' => 'bg-blue-50'],
                'student'      => ['icon' => 'ti-school',     'color' => 'purple', 'bg' => 'bg-purple-50'],
                'worker'       => ['icon' => 'ti-briefcase',  'color' => 'teal',   'bg' => 'bg-teal-50'],
                'investor'     => ['icon' => 'ti-chart-line', 'color' => 'amber',  'bg' => 'bg-amber-50'],
                'retiree'      => ['icon' => 'ti-home-heart', 'color' => 'green',  'bg' => 'bg-green-50'],
                'family'       => ['icon' => 'ti-heart',      'color' => 'pink',   'bg' => 'bg-pink-50'],
                'thai_citizen' => ['icon' => 'ti-flag',       'color' => 'red',    'bg' => 'bg-red-50'],
                'hotel'        => ['icon' => 'ti-building',   'color' => 'orange', 'bg' => 'bg-orange-50'],
                'employer'     => ['icon' => 'ti-users',      'color' => 'slate',  'bg' => 'bg-slate-100'],
                'officer'      => ['icon' => 'ti-shield-check','color' => 'indigo', 'bg' => 'bg-indigo-50'],
                'general'      => ['icon' => 'ti-user',       'color' => 'slate',  'bg' => 'bg-slate-100'],
            ];
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach ($personaIcons as $persona => $cfg)
                <button type="button" @click="selectedPersona = '{{ $persona }}'"
                        class="group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-slate-200
                               hover:border-blue-300 hover:shadow-md transition-all duration-200 text-center">
                    <div class="w-14 h-14 {{ $cfg['bg'] }} rounded-2xl flex items-center justify-center
                                group-hover:scale-110 transition-transform duration-200">
                        <i class="ti {{ $cfg['icon'] }} text-{{ $cfg['color'] }}-600 text-2xl"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 group-hover:text-blue-700">
                        {{ __("personas.{$persona}") }}
                    </span>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Step 2: What do you want to do? --}}
        <div x-show="selectedPersona"             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">
                    {{ __('smart_guide.quick_topics') }}
                </h2>
                <button type="button" @click="selectedPersona = null"
                        class="text-xs font-medium text-blue-700 hover:text-blue-800">
                    ← {{ __('smart_guide.change_persona') }}
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach ($topics as $topic)
                <a x-show="(topicsByPersona[selectedPersona] || []).includes('{{ $topic }}')"
                   :href="`{{ url('smart-guide') }}/${selectedPersona}/{{ $topic }}`"
                   class="flex items-center gap-2 px-4 py-3 bg-white border border-slate-200 rounded-xl
                          text-sm font-medium text-slate-700 hover:bg-blue-700 hover:text-white
                          hover:border-blue-700 transition-colors">
                    {{ __("topics.{$topic}") }}
                </a>
                @endforeach
            </div>

            <div x-show="(topicsByPersona[selectedPersona] || []).length === 0" class="text-center py-10 text-slate-400">
                <i class="ti ti-mood-empty text-4xl mb-2 block"></i>
                <p class="text-sm">{{ __('smart_guide.no_content') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
