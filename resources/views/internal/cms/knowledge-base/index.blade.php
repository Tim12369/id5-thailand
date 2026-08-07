@extends('internal.layouts.app')

@section('title', 'Knowledge Base')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Knowledge Base</h2>
            <p class="text-sm text-slate-400 mt-1">Manage the public Knowledge Base articles.</p>
        </div>
        <a href="{{ route('internal.cms.knowledge-base.create') }}" class="btn-portal-primary">
            <i class="ti ti-plus"></i> Create Article
        </a>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($articles as $article)
                <div class="flex items-center gap-4 p-4">
                    <div class="shrink-0 w-16 h-16 rounded-lg bg-white/5 overflow-hidden">
                        @if ($article->thumbnail_url)
                            <img src="{{ $article->thumbnail_url }}" alt="" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-500">
                                <i class="ti ti-bulb text-xl"></i>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $article->title_en ?: $article->title_th }}</p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            @if ($article->status === 'published')
                                <span class="badge-success">Published</span>
                            @else
                                <span class="badge-neutral">Draft</span>
                            @endif
                            @if ($article->category)
                                <span class="badge-info">{{ $article->category->getLocalizedName() }}</span>
                            @endif
                            <span class="text-xs text-slate-500">{{ $article->published_at?->format('d M Y') ?? 'Not scheduled' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('internal.cms.knowledge-base.toggle', $article) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary btn-sm">
                                {{ $article->status === 'published' ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <a href="{{ route('internal.cms.knowledge-base.edit', $article) }}" class="btn-secondary btn-sm">
                            <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('internal.cms.knowledge-base.destroy', $article) }}" onsubmit="return confirm('Delete this article?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger-ghost btn-sm">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No Knowledge Base articles yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $articles->links() }}
    </div>
@endsection
