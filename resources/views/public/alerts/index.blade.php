@extends('public.layouts.app')
@section('title', __('alerts.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-1">{{ __('alerts.title') }}</h1>
        <p class="text-slate-500 text-sm">{{ __('alerts.subtitle') }}</p>
    </div>

    <div class="space-y-3">
        @forelse ($alerts as $alert)
        @cmsEdit($alert)
        <div class="flex gap-4 p-4 rounded-2xl border
            @if ($alert->alert_level === 'danger') bg-red-50 border-red-200
            @elseif ($alert->alert_level === 'warning') bg-amber-50 border-amber-200
            @else bg-blue-50 border-blue-200 @endif">

            <div class="shrink-0 mt-0.5">
                @if ($alert->alert_level === 'danger')
                    <i class="ti ti-alert-circle text-red-600 text-xl"></i>
                @elseif ($alert->alert_level === 'warning')
                    <i class="ti ti-alert-triangle text-amber-600 text-xl"></i>
                @else
                    <i class="ti ti-info-circle text-blue-600 text-xl"></i>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <h3 class="font-semibold text-sm
                        @if ($alert->alert_level === 'danger') text-red-900
                        @elseif ($alert->alert_level === 'warning') text-amber-900
                        @else text-blue-900 @endif">
                        {{ $alert->getLocalizedTitle() }}
                    </h3>
                    <span class="text-xs text-slate-400 shrink-0">
                        {{ $alert->published_at?->format('d M Y') }}
                    </span>
                </div>
                @if ($alert->getLocalizedExcerpt())
                <p class="text-xs leading-relaxed
                    @if ($alert->alert_level === 'danger') text-red-800
                    @elseif ($alert->alert_level === 'warning') text-amber-800
                    @else text-blue-800 @endif">
                    {{ $alert->getLocalizedExcerpt() }}
                </p>
                @endif

                {{-- The full text and pictures, when the editor wrote more than the summary --}}
                @if ($alert->thumbnail_url)
                <img src="{{ $alert->thumbnail_url }}" alt="{{ $alert->getLocalizedTitle() }}" loading="lazy"
                     class="mt-3 w-full h-auto rounded-xl">
                @endif
                @if ($alert->hasBodyBeyondExcerpt())
                <div class="prose prose-sm max-w-none mt-3 text-slate-700">
                    {!! $alert->getLocalizedBody() !!}
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-16 text-slate-400">
            <i class="ti ti-circle-check text-5xl text-green-400 mb-3 block"></i>
            <p class="font-medium text-slate-600">{{ __('alerts.no_alerts') }}</p>
        </div>
        @endforelse
    @cmsAdd('new:id5_alert', 'เพิ่มการแจ้งเตือน', null, true)</div>

    <div class="mt-6">{{ $alerts->links() }}</div>
</div>
@endsection
