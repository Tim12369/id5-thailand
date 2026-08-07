{{-- components/public/action-card.blade.php --}}
{{-- Props: $href, $icon, $title, $desc, $color = 'blue' --}}

@props(['href', 'icon', 'title', 'desc', 'color' => 'blue'])

<a href="{{ $href }}" class="card card-hover focus-ring flex items-center gap-4 p-4 lg:p-5">
    <span class="icon-tile w-12 h-12 lg:w-14 lg:h-14 bg-{{ $color }}-50 text-{{ $color }}-600 shrink-0">
        <i class="ti {{ $icon }} text-2xl"></i>
    </span>
    <span class="min-w-0">
        <span class="block font-semibold text-slate-800 text-sm lg:text-base leading-tight">{{ $title }}</span>
        <span class="block text-xs text-slate-500 leading-snug mt-0.5 line-clamp-1">{{ $desc }}</span>
    </span>
    <i class="ti ti-chevron-right text-slate-300 ml-auto shrink-0"></i>
</a>
