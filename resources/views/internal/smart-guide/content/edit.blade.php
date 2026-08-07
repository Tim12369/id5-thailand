@extends('internal.layouts.app')

@section('title', "Edit Content — {$persona} / {$topic}")

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ ucfirst($persona) }} — {{ $topic }}</h2>
        <p class="text-sm text-slate-400 mt-1">Fill in as many dimensions as apply. Leave a dimension blank to keep it hidden on the public page.</p>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.smart-guide-content.update', [$persona, $topic]) }}" class="space-y-5 max-w-4xl">
        @csrf
        @method('PUT')

        @foreach ($dimensions as $dimension)
            @php $row = $rows->get($dimension); @endphp
            <div class="portal-card p-5">
                <h3 class="text-sm font-semibold text-white uppercase tracking-wide mb-3">{{ $dimension }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="portal-label">Thai</label>
                        <textarea name="content_th[{{ $dimension }}]" rows="4" class="portal-input">{{ old("content_th.{$dimension}", $row?->content_th) }}</textarea>
                    </div>
                    <div>
                        <label class="portal-label">English</label>
                        <textarea name="content_en[{{ $dimension }}]" rows="4" class="portal-input">{{ old("content_en.{$dimension}", $row?->content_en) }}</textarea>
                    </div>
                    <div>
                        <label class="portal-label">Chinese</label>
                        <textarea name="content_zh[{{ $dimension }}]" rows="4" class="portal-input">{{ old("content_zh.{$dimension}", $row?->content_zh) }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.smart-guide-content.index') }}" class="btn-secondary">Back to Grid</a>
        </div>
    </form>
@endsection
