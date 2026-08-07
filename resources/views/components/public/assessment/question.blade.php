{{-- One question block (fieldset/legend for a11y). Visibility is computed
     client-side by the parent Alpine component from the config showIf rules,
     giving progressive disclosure of follow-up questions. --}}
@props(['node'])

<fieldset x-show="isVisible('{{ $node['id'] }}')" x-cloak
          x-data="{ explaining: false }"
          role="{{ ($node['type'] ?? 'single') === 'multi' ? 'group' : 'radiogroup' }}"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 -translate-y-1"
          x-transition:enter-end="opacity-100 translate-y-0">
    <div class="mb-3">
        <div class="flex items-start justify-between gap-2">
            <legend id="q-{{ $node['id'] }}-label" class="text-base font-semibold text-slate-800">
                {{ $node['title'] }}
            </legend>
            @if ($node['help'] ?? null)
                <button type="button" @click="explaining = !explaining"
                        :aria-expanded="explaining.toString()"
                        aria-controls="q-{{ $node['id'] }}-explain"
                        class="focus-ring shrink-0 inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 px-2 py-1 rounded-lg hover:bg-blue-50">
                    <i class="ti ti-info-circle"></i>
                    <span x-text="explaining ? '{{ __('assessment.explain_close') }}' : '{{ __('assessment.explain') }}'"></span>
                </button>
            @endif
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
            {{ ($node['type'] ?? 'single') === 'multi' ? __('assessment.select_many') : __('assessment.select_one') }}
        </p>
        @if ($node['help'] ?? null)
            <p id="q-{{ $node['id'] }}-explain" x-show="explaining" x-cloak
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="opacity-0 -translate-y-1"
               x-transition:enter-end="opacity-100 translate-y-0"
               class="text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2 mt-2 leading-relaxed">
                {{ $node['help'] }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
        @foreach ($node['options'] as $opt)
            <x-public.assessment.option-tile
                :question="$node['id']"
                :type="$node['type'] ?? 'single'"
                :value="$opt['value']"
                :icon="$opt['icon']"
                :label="$opt['label']"
                :first="$loop->first" />
        @endforeach
    </div>
</fieldset>
