@extends('internal.layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Audit Logs</h2>
        <p class="text-sm text-slate-400 mt-1">
            Tracks data changes across the portal. Currently populated by Executive Dashboard
            actions; more modules will log here as they adopt this same audit trail.
        </p>
    </div>

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($logs as $log)
                <div class="flex items-center gap-4 p-4">
                    <span class="badge-info shrink-0">{{ $log->action }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-100">{{ $log->description }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('d M Y H:i') }}
                            @if ($log->target_type) &middot; {{ class_basename($log->target_type) }} #{{ $log->target_id }} @endif
                        </p>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No audit log entries yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
