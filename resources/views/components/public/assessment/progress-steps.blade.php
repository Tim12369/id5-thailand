{{-- Timeline over the stages that actually apply to the current branch
     (Alpine `applicableStages`), so skipped stages never appear. Numbering
     reflects position, not the raw stage id. --}}
<ol class="flex items-center" aria-label="{{ __('assessment.eyebrow') }}">
    <template x-for="(s, i) in applicableStages" :key="s">
        <li class="flex items-center" :class="i < applicableStages.length - 1 ? 'flex-1' : ''">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors"
                      :class="progressIndex > i
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : (progressIndex === i
                              ? 'bg-white border-blue-600 text-blue-600 ring-4 ring-blue-100'
                              : 'bg-white border-slate-200 text-slate-300')"
                      :aria-current="progressIndex === i ? 'step' : false">
                    <span x-show="progressIndex <= i" x-text="i + 1"></span>
                    <i class="ti ti-check" x-show="progressIndex > i" x-cloak></i>
                </span>
                <span class="hidden md:block text-xs font-medium leading-tight truncate max-w-32 transition-colors"
                      :class="progressIndex >= i ? 'text-slate-700' : 'text-slate-400'"
                      x-text="stageName(s)"></span>
            </div>
            <span x-show="i < applicableStages.length - 1"
                  class="flex-1 h-0.5 mx-2 sm:mx-3 rounded-full transition-colors"
                  :class="progressIndex > i ? 'bg-blue-500' : 'bg-slate-200'"></span>
        </li>
    </template>
</ol>
