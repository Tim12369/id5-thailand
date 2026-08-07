@extends('internal.layouts.app')

@section('title', 'Website Settings')

@section('content')
    @php($canManage = auth()->user()->hasPermission('settings.manage'))

    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Website Settings</h2>
        <p class="text-sm text-slate-400 mt-1">Site identity shown across every public page's header and footer.</p>
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

    <form method="POST" action="{{ route('internal.website-settings.update') }}" class="portal-card p-5 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Site name</label>
                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" class="portal-input" required>
            </div>
            <div>
                <label class="portal-label">Emergency hotline</label>
                <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $emergencyPhone) }}" class="portal-input" required>
            </div>
        </div>

        @if ($canManage)
            <button type="submit" class="btn-portal-primary mt-6">Save</button>
        @endif
    </form>
@endsection
