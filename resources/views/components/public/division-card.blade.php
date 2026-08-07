{{-- components/public/division-card.blade.php --}}
{{-- Props: $href (navigates directly) OR $dispatch (fires a window event for another
     component to react to — used by the two cards that reuse existing features
     instead of navigating), $icon, $gradient (Tailwind from-/to- classes), $title, $desc,
     $photo (optional real photo URL — admin-uploaded via Website Management > Homepage
     Divisions; falls back to the icon+gradient "illustration" when unset, same
     graceful-fallback convention used by the hero image/text elsewhere on this page) --}}

@props(['href' => null, 'dispatch' => null, 'icon', 'gradient', 'title', 'desc' => null, 'photo' => null])

@php
    $tag = $href ? 'a' : 'button';
    $attrs = $href
        ? ['href' => $href]
        : ['type' => 'button', '@click' => "\$dispatch('{$dispatch}')"];
@endphp

<{{ $tag }} {{ $attributes->merge($attrs)->merge(['class' =>
        'focus-ring group relative flex flex-col justify-end overflow-hidden rounded-2xl aspect-[4/3] sm:aspect-square
         shadow-[0_4px_16px_rgba(15,23,42,0.08)] transition-all duration-250 ease-out
         hover:-translate-y-1 hover:scale-[1.02] hover:shadow-[0_16px_32px_rgba(15,23,42,0.18)] cursor-pointer text-left w-full'
    ]) }}
    aria-label="{{ $title }}{{ $desc ? ' — ' . $desc : '' }}">

    @if ($photo)
        {{-- Admin-uploaded photo, same treatment as the About Us org chart's leadership plates --}}
        <img src="{{ $photo }}" alt="" class="absolute inset-0 w-full h-full object-cover transition-transform duration-250 group-hover:scale-105">
    @else
        {{-- Gradient "illustration" fallback for divisions with no photo uploaded yet --}}
        <div class="absolute inset-0 bg-linear-to-br {{ $gradient }}"></div>
        <div class="absolute inset-0 pattern-dots opacity-10"></div>
        <i class="ti {{ $icon }} absolute -right-4 -top-4 text-9xl text-white/10 rotate-12 transition-transform duration-250 group-hover:scale-110 group-hover:rotate-6" aria-hidden="true"></i>
    @endif

    {{-- Readability overlay --}}
    <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/20 to-transparent"></div>

    <div class="relative p-5 sm:p-6">
        <span class="icon-tile w-11 h-11 bg-white/15 text-white backdrop-blur-sm mb-3">
            <i class="ti {{ $icon }} text-xl"></i>
        </span>
        <h3 class="text-white font-bold text-lg leading-snug text-balance">{{ $title }}</h3>
        @if ($desc)
            <p class="text-white/80 text-xs mt-1 leading-relaxed">{{ $desc }}</p>
        @endif
    </div>
</{{ $tag }}>
