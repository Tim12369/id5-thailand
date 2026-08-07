@extends('internal.layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Welcome back, {{ auth()->user()->name }}</h2>
        <p class="text-sm text-slate-400 mt-1">{{ auth()->user()->position ?? 'Immigration Bureau 5' }} — here's your portal at a glance.</p>
    </div>

    @php
        // Flatten both top-level links and group children into one list of
        // tiles, so every destination in the sidebar stays one click away
        // from this welcome grid regardless of how it's nested.
        $tiles = [];
        foreach (\App\Support\PortalNav::items() as $item) {
            if ($item['type'] === 'link') {
                $tiles[] = $item;
            } else {
                foreach ($item['children'] as $child) {
                    $tiles[] = ['route' => $child['route'], 'label' => $child['label'], 'icon' => $item['icon']];
                }
            }
        }
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($tiles as $tile)
            @if ($tile['route'] !== 'internal.dashboard')
                <a href="{{ route($tile['route']) }}" class="portal-module-tile">
                    <span class="portal-tile-icon">
                        <i class="ti {{ $tile['icon'] }} text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-white">{{ $tile['label'] }}</h3>
                        <p class="text-sm text-slate-400 mt-1">Open {{ $tile['label'] }}</p>
                    </div>
                </a>
            @endif
        @endforeach
    </div>
@endsection
