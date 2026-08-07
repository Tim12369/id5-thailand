@extends('internal.layouts.app')

@section('title', 'Content Management')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Content Management</h2>
        <p class="text-sm text-slate-400 mt-1">Manage the public homepage's News, Announcements, and Alerts.</p>
    </div>

    {{-- Stat widgets --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="portal-card p-5">
            <p class="text-sm text-slate-400">Total News</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $stats['total_news'] }}</p>
        </div>
        <div class="portal-card p-5">
            <p class="text-sm text-slate-400">Active Announcements</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $stats['active_announcements'] }}</p>
        </div>
        <div class="portal-card p-5">
            <p class="text-sm text-slate-400">Active Alerts</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $stats['active_alerts'] }}</p>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('internal.cms.news.index') }}" class="portal-module-tile portal-card-hover">
            <span class="portal-tile-icon"><i class="ti ti-news text-2xl"></i></span>
            <div>
                <h3 class="text-base font-semibold text-white">Latest News</h3>
                <p class="text-sm text-slate-400 mt-1">Create and manage news articles</p>
            </div>
        </a>
        <a href="{{ route('internal.cms.announcements.index') }}" class="portal-module-tile portal-card-hover">
            <span class="portal-tile-icon"><i class="ti ti-speakerphone text-2xl"></i></span>
            <div>
                <h3 class="text-base font-semibold text-white">Announcements</h3>
                <p class="text-sm text-slate-400 mt-1">Manage public announcements</p>
            </div>
        </a>
        <a href="{{ route('internal.cms.alerts.index') }}" class="portal-module-tile portal-card-hover">
            <span class="portal-tile-icon"><i class="ti ti-alert-triangle text-2xl"></i></span>
            <div>
                <h3 class="text-base font-semibold text-white">Alerts</h3>
                <p class="text-sm text-slate-400 mt-1">Manage urgent homepage alerts</p>
            </div>
        </a>
    </div>

    {{-- Recently published --}}
    <div class="portal-card p-5">
        <h3 class="text-base font-semibold text-white mb-4">Recently Published Content</h3>
        @forelse ($recent as $item)
            <div class="flex items-center justify-between gap-4 py-2.5 border-b border-white/5 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-200 truncate">{{ $item->title_en ?: $item->title_th }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ ucfirst($item->type) }} &middot; {{ $item->published_at?->format('d M Y') }}</p>
                </div>
                <span class="badge-success shrink-0">Published</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">No published content yet.</p>
        @endforelse
    </div>
@endsection
