{{-- components/public/persona-item.blade.php --}}
{{-- Props: $href (direct link) OR $persona (opens the "Who Are You?" popup), $icon, $label, $color = 'blue' --}}

@props(['href' => null, 'persona' => null, 'icon', 'label', 'color' => 'blue'])

@if ($persona)
<button type="button" @click="goToPersona('{{ $persona }}')"
        class="card card-hover focus-ring group flex flex-col items-center gap-2.5 p-4 text-center w-28 sm:w-auto shrink-0">
@else
<a href="{{ $href }}"
   class="card card-hover focus-ring group flex flex-col items-center gap-2.5 p-4 text-center w-28 sm:w-auto shrink-0">
@endif
    <span class="icon-tile w-12 h-12 lg:w-14 lg:h-14 bg-{{ $color }}-50 text-{{ $color }}-600
                 group-hover:bg-{{ $color }}-100">
        <i class="ti {{ $icon }} text-2xl"></i>
    </span>
    <span class="text-xs lg:text-sm font-medium text-slate-700 group-hover:text-blue-700 leading-tight">
        {{ $label }}
    </span>
@if ($persona)
</button>
@else
</a>
@endif
