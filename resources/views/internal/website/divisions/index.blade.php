@extends('internal.layouts.app')

@section('title', 'Homepage Divisions')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Homepage Divisions</h2>
        <p class="text-sm text-slate-400 mt-1">Edit the photo and text for all 6 cards on the homepage "Explore ID5 Services" grid.</p>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @foreach ($cards as $card)
                <div class="flex items-center gap-4 p-4">
                    <div class="shrink-0 w-16 h-12 rounded-lg overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center">
                        @if ($card->photo_url)
                            <img src="{{ $card->photo_url }}" alt="" class="w-full h-full object-cover">
                        @else
                            <i class="ti ti-photo-off text-slate-500"></i>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $card->title_th }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $card->title_en }}</p>
                    </div>

                    @if ($card->photo_url)
                        <span class="badge-success shrink-0"><i class="ti ti-photo mr-1"></i>Photo set</span>
                    @else
                        <span class="badge-neutral shrink-0"><i class="ti ti-photo-off mr-1"></i>No photo</span>
                    @endif

                    <a href="{{ route('internal.website-divisions.edit', $card) }}" class="btn-secondary btn-sm shrink-0">
                        <i class="ti ti-edit"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
