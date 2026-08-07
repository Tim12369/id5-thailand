{{-- components/public/guide-item.blade.php --}}
{{-- Props: $href, $icon, $title, $desc, $color = 'blue' --}}

@props(['href', 'icon', 'title', 'desc', 'color' => 'blue'])

<a href="{{ $href }}"
   class="card card-hover focus-ring group flex flex-col gap-3 p-5 w-48 sm:w-auto shrink-0">
    <span class="icon-tile w-12 h-12 bg-{{ $color }}-50 text-{{ $color }}-600 group-hover:bg-{{ $color }}-100">
        <i class="ti {{ $icon }} text-2xl"></i>
    </span>
    <div>
        <h3 class="font-semibold text-slate-800 group-hover:text-blue-700 mb-1">{{ $title }}</h3>
        <p class="text-xs text-slate-500 leading-relaxed">{{ $desc }}</p>
    </div>
</a>
