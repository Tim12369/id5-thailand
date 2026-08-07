{{-- components/public/news-column.blade.php --}}
{{-- Props: $icon, $title, $items, $viewAllRoute = null, $emptyText --}}

@props(['icon', 'title', 'items', 'viewAllRoute' => null, 'emptyText'])

<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h2 class="flex items-center gap-2 font-semibold text-slate-800">
            <i class="ti {{ $icon }} text-blue-600"></i> {{ $title }}
        </h2>
        @if ($viewAllRoute)
        <a href="{{ $viewAllRoute }}" class="focus-ring text-xs font-medium text-blue-700 hover:text-blue-800 px-1.5 py-1">
            {{ __('home.view_all') }} →
        </a>
        @endif
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($items as $article)
            @include('components.public.article-card', ['article' => $article, 'variant' => 'compact'])
        @empty
        <div class="p-6 text-center">
            <i class="ti ti-news-off text-2xl text-slate-300 mb-2 block"></i>
            <p class="text-slate-400 text-sm">{{ $emptyText }}</p>
        </div>
        @endforelse
    </div>
</div>
