{{-- components/public/article-card.blade.php --}}
{{-- Props: $article (Article model), $variant = 'default' | 'compact' --}}

@props(['article', 'variant' => 'default'])

@if ($variant === 'compact')
@php
$compactRouteMap = [
    'news'         => ['name' => 'public.news.show',          'param' => $article->slug],
    'announcement' => ['name' => 'public.announcements.show', 'param' => $article->slug],
    'kb_public'    => ['name' => 'public.knowledge.show',     'param' => $article->slug],
    'alert'        => ['name' => 'public.alerts',             'param' => null],
];
$cr = $compactRouteMap[$article->type] ?? ['name' => 'public.news.show', 'param' => $article->slug];
$compactHref = $cr['param'] ? route($cr['name'], $cr['param']) : route($cr['name']);
@endphp
@cmsEdit($article)
<a href="{{ $compactHref }}"
   class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 group transition-colors">
    <div class="shrink-0 w-16 h-16 rounded-lg bg-slate-100 overflow-hidden">
        @if ($article->thumbnail_url)
            <img src="{{ $article->thumbnail_url }}" alt="" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-400">
                <i class="ti ti-news text-xl"></i>
            </div>
        @endif
    </div>
    <div class="min-w-0">
        <p class="text-xs text-slate-400 mb-0.5">{{ $article->published_at?->format('d M Y') }}</p>
        <p class="text-sm font-medium text-slate-800 group-hover:text-blue-700 line-clamp-2 leading-snug">
            {{ $article->getLocalizedTitle() }}
        </p>
    </div>
</a>

@else

@php
$routeMap = [
    'news'         => 'public.news.show',
    'announcement' => 'public.announcements.show',
    'kb_public'    => 'public.knowledge.show',
    'alert'        => 'public.alerts',
];
$route = $routeMap[$article->type] ?? 'public.news.show';
@endphp

@cmsEdit($article)
<a href="{{ $article->type === 'alert' ? route($route) : route($route, $article->slug) }}"
   class="card card-hover flex gap-4 p-4 group">

    {{-- Thumbnail --}}
    <div class="shrink-0 w-24 h-20 rounded-xl bg-slate-100 overflow-hidden">
        @if ($article->thumbnail_url)
            <img src="{{ $article->thumbnail_url }}"
                 alt="{{ $article->getLocalizedTitle() }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-300">
                <i class="ti ti-news text-3xl"></i>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 mb-1 flex-wrap">
            @if ($article->category)
            <span class="badge-info">
                {{ $article->category->getLocalizedName() }}
            </span>
            @endif
            @if ($article->is_pinned)
            <span class="badge-warning">
                <i class="ti ti-pin text-[9px]"></i> {{ __('article.pinned') }}
            </span>
            @endif
            <span class="text-xs text-slate-400 ml-auto">
                {{ $article->published_at?->diffForHumans() }}
            </span>
        </div>

        <h3 class="font-semibold text-slate-800 text-sm group-hover:text-blue-700
                   line-clamp-2 leading-snug transition-colors mb-1">
            {{ $article->getLocalizedTitle() }}
        </h3>

        @if ($article->getLocalizedExcerpt())
        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
            {{ $article->getLocalizedExcerpt() }}
        </p>
        @endif
    </div>
</a>
@endif
