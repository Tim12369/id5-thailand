{{-- components/public/cta-banner-card.blade.php --}}
{{-- Props: $href, $icon, $title, $desc, $cta, $variant = 'primary' (primary|danger|line) --}}

@props(['href', 'icon', 'title', 'desc', 'cta', 'variant' => 'primary'])

<a href="{{ $href }}"
   class="cta-banner-{{ $variant }} focus-ring group relative overflow-hidden rounded-3xl p-6 lg:p-8 flex flex-col gap-4">
    <div class="absolute inset-0 pattern-dots opacity-15"></div>
    <span class="relative icon-tile w-12 h-12 bg-white/15 text-white">
        <i class="ti {{ $icon }} text-2xl"></i>
    </span>
    <div class="relative">
        <h3 class="text-lg font-bold mb-1">{{ $title }}</h3>
        <p class="text-sm text-white/80 leading-relaxed">{{ $desc }}</p>
    </div>
    <span class="relative mt-auto inline-flex items-center gap-1.5 text-sm font-semibold group-hover:gap-2.5 transition-all duration-200">
        {{ $cta }} <i class="ti ti-arrow-right text-sm"></i>
    </span>
</a>
