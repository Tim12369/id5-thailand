{{-- Mandatory non-binding-assessment notice. Shown in-wizard and on the result.
     `prominent` renders the amber emphasized variant used on the result page. --}}
@props(['prominent' => false])

@if ($prominent)
<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4']) }}>
    <i class="ti ti-info-circle text-amber-500 text-lg shrink-0 mt-0.5" aria-hidden="true"></i>
    <p class="text-sm text-amber-800 leading-relaxed">{{ __('assessment.disclaimer_body') }}</p>
</div>
@else
<div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3']) }}>
    <i class="ti ti-shield-lock text-slate-400 text-base shrink-0 mt-0.5" aria-hidden="true"></i>
    <p class="text-xs text-slate-500 leading-relaxed">{{ __('assessment.disclaimer_body') }}</p>
</div>
@endif
