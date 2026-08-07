@extends('internal.layouts.app')

@section('title', 'Smart Guide Topics')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Smart Guide Topics</h2>
            <p class="text-sm text-slate-400 mt-1">Manage each topic's icon, deadline, required documents, steps, and locations — shared across every persona.</p>
        </div>
        <a href="{{ route('internal.smart-guide-topics.create') }}" class="btn-portal-primary">
            <i class="ti ti-plus"></i> Add Topic
        </a>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($topics as $topic)
                <div class="flex items-center gap-4 p-4">
                    <span class="icon-tile w-10 h-10 shrink-0" style="background-color: color-mix(in srgb, {{ $topic->color ?? '#4F46E5' }} 20%, transparent); color: {{ $topic->color ?? '#4F46E5' }}">
                        <i class="ti {{ $topic->icon ?: 'ti-file-text' }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100">{{ $topic->topic }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $topic->documents_count }} document(s) &middot; {{ $topic->steps_count }} step(s) &middot; {{ $topic->locations_count }} location(s)
                            @if ($topic->deadline_days) &middot; deadline: every {{ $topic->deadline_days }} days @endif
                        </p>
                    </div>
                    @unless ($topic->is_active)
                        <span class="badge-neutral shrink-0">Inactive</span>
                    @endunless
                    @if ($topic->is_urgent)
                        <span class="badge-danger shrink-0">Urgent</span>
                    @endif
                    <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}" class="btn-secondary btn-sm shrink-0">
                        <i class="ti ti-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('internal.smart-guide-topics.destroy', $topic) }}" onsubmit="return confirm('Delete this topic and all its documents/steps/locations?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger-ghost btn-sm">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No topics yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('internal.smart-guide-content.index') }}" class="text-sm text-brand-400 hover:text-brand-300">
            <i class="ti ti-arrow-right"></i> Manage per-persona content (Who/What/Why/Where/When/How) →
        </a>
    </div>
@endsection
