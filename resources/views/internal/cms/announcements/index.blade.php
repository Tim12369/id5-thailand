@extends('internal.layouts.app')

@section('title', 'Announcements')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Announcements</h2>
            <p class="text-sm text-slate-400 mt-1">Manage announcements shown on the public homepage.</p>
        </div>
        <a href="{{ route('internal.cms.announcements.create') }}" class="btn-portal-primary">
            <i class="ti ti-plus"></i> Create Announcement
        </a>
    </div>

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($articles as $article)
                @php
                    $priorityBadge = match ($article->priority) {
                        'critical' => 'badge-danger',
                        'high' => 'badge-warning',
                        'medium' => 'badge-info',
                        default => 'badge-neutral',
                    };
                @endphp
                <div class="flex items-center gap-4 p-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $article->title_en ?: $article->title_th }}</p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            @if ($article->status === 'published')
                                <span class="badge-success">Published</span>
                            @else
                                <span class="badge-neutral">Draft</span>
                            @endif
                            <span class="{{ $priorityBadge }}">{{ ucfirst($article->priority ?? 'medium') }}</span>
                            <span class="text-xs text-slate-500">
                                {{ $article->published_at?->format('d M Y') ?? 'No start date' }}
                                @if ($article->expired_at) &rarr; {{ $article->expired_at->format('d M Y') }} @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('internal.cms.announcements.toggle', $article) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary btn-sm">
                                {{ $article->status === 'published' ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <a href="{{ route('internal.cms.announcements.edit', $article) }}" class="btn-secondary btn-sm">
                            <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('internal.cms.announcements.destroy', $article) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger-ghost btn-sm">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No announcements yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $articles->links() }}
    </div>
@endsection
