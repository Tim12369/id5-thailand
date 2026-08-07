{{-- One selectable answer. Radio (single) or checkbox (multi) semantics.
     All state lives in the parent `assessment()` Alpine component.
     Single-choice options form a proper WCAG radiogroup: roving tabindex
     (only the selected — or first — option is tab-reachable) and arrow keys
     move focus/selection between siblings. --}}
@props(['question', 'type' => 'single', 'value', 'icon' => 'ti-point', 'label', 'first' => false])

<button type="button"
        @if ($type === 'multi')
            role="checkbox"
            tabindex="0"
        @else
            role="radio"
            :tabindex="rovingTab('{{ $question }}', '{{ $value }}', {{ $first ? 'true' : 'false' }})"
            @keydown.arrow-up.prevent="focusSibling($event, -1)"
            @keydown.arrow-left.prevent="focusSibling($event, -1)"
            @keydown.arrow-down.prevent="focusSibling($event, 1)"
            @keydown.arrow-right.prevent="focusSibling($event, 1)"
        @endif
        @click="select('{{ $question }}', '{{ $value }}', '{{ $type }}')"
        :aria-checked="isSelected('{{ $question }}', '{{ $value }}') ? 'true' : 'false'"
        :class="isSelected('{{ $question }}', '{{ $value }}')
            ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/25 text-blue-900'
            : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50 text-slate-700'"
        class="focus-ring group flex items-center gap-3 w-full text-left p-4 rounded-2xl border-2 transition-all duration-150 min-h-[3.75rem]">
    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
          :class="isSelected('{{ $question }}', '{{ $value }}')
              ? 'bg-blue-500 text-white'
              : 'bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600'">
        <i class="ti {{ $icon }} text-xl"></i>
    </span>
    <span class="flex-1 text-sm font-medium leading-snug">{{ $label }}</span>
    <i class="ti ti-circle-check-filled text-blue-600 text-xl shrink-0 transition-opacity duration-150"
       :class="isSelected('{{ $question }}', '{{ $value }}') ? 'opacity-100' : 'opacity-0'"
       aria-hidden="true"></i>
</button>
