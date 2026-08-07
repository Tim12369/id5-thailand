{{-- Back / Continue / Review. Continue is disabled until every visible
     question in the current stage is answered (`canContinue`). The last stage
     goes to a Review screen (startReview) rather than submitting directly. --}}
<div class="flex items-center justify-between gap-3 mt-8" x-show="!reviewing">
    <button type="button" @click="back()" x-show="!isFirstStage" x-cloak
            class="focus-ring inline-flex items-center gap-1.5 px-5 py-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
        <i class="ti ti-arrow-left"></i> {{ __('assessment.back') }}
    </button>
    {{-- keeps Continue right-aligned on the first stage --}}
    <span x-show="isFirstStage"></span>

    <button type="button" @click="next()" x-show="!isLastStage"
            :disabled="!canContinue"
            :class="canContinue ? 'bg-blue-600 hover:bg-blue-700' : 'bg-slate-300 cursor-not-allowed'"
            class="focus-ring inline-flex items-center gap-1.5 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-colors">
        {{ __('assessment.continue') }} <i class="ti ti-arrow-right"></i>
    </button>

    <button type="button" @click="startReview()" x-show="isLastStage" x-cloak
            :disabled="!canContinue"
            :class="canContinue ? 'bg-blue-600 hover:bg-blue-700' : 'bg-slate-300 cursor-not-allowed'"
            class="focus-ring inline-flex items-center gap-1.5 px-6 py-3 rounded-xl text-sm font-semibold text-white transition-colors">
        {{ __('assessment.view_results') }} <i class="ti ti-arrow-right"></i>
    </button>
</div>
