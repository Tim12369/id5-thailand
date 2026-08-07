@extends('internal.layouts.app')

@section('title', 'Appearance')

@section('content')
    @php($canManage = auth()->user()->hasPermission('settings.manage'))

    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Appearance</h2>
        <p class="text-sm text-slate-400 mt-1">
            Brand accent color, saved for future theming work. The public site's color palette is
            currently compiled at build time, so this value isn't yet reflected live &mdash; it's
            reserved here so a future theming pass has a real setting to read from.
        </p>
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

    <form method="POST" action="{{ route('internal.appearance.update') }}" class="portal-card p-5 max-w-md">
        @csrf
        @method('PUT')

        <label class="portal-label">Accent color</label>
        <div class="flex items-center gap-3">
            <input type="color" name="accent_color" value="{{ old('accent_color', $accentColor) }}" class="portal-input h-11 w-20 p-1">
            <span class="text-sm text-slate-300">{{ old('accent_color', $accentColor) }}</span>
        </div>

        @if ($canManage)
            <button type="submit" class="btn-portal-primary mt-6">Save</button>
        @endif
    </form>
@endsection
