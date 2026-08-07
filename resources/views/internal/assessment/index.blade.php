@extends('internal.layouts.app')

@section('title', 'Assessment Results')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Assessment Results</h2>
        <p class="text-sm text-slate-400 mt-1">Author the guidance shown by the guided "Who Are You?" assessment for each inferred status (TH/EN/ZH). The list of statuses is fixed — you fill in the content.</p>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    @php
        $riskBadge = [
            'info'   => 'bg-slate-500/15 text-slate-300 border-slate-400/20',
            'low'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-400/20',
            'medium' => 'bg-amber-500/15 text-amber-300 border-amber-400/20',
            'high'   => 'bg-red-500/15 text-red-300 border-red-400/20',
        ];
    @endphp

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @foreach ($statuses as $key => $def)
                @php $rec = $recs->get($key); $filled = $rec?->authoredCount() ?? 0; @endphp
                <div class="flex items-center gap-4 p-4">
                    <div class="w-10 h-10 rounded-lg bg-white/5 text-slate-300 flex items-center justify-center shrink-0">
                        <i class="ti {{ $def['icon'] ?? 'ti-user' }} text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100">{{ $def['label']['en'] ?? $key }}</p>
                        <p class="text-xs text-slate-400">{{ $def['label']['th'] ?? '' }}</p>
                    </div>

                    <span class="shrink-0 text-xs font-medium px-2 py-1 rounded-full border {{ $riskBadge[$rec?->risk_level ?? 'info'] }}">
                        {{ ucfirst($rec?->risk_level ?? 'info') }}
                    </span>

                    <span class="shrink-0 text-xs font-medium px-2 py-1 rounded-lg {{ $filled > 0 ? 'bg-blue-500/15 text-blue-300' : 'bg-white/5 text-slate-500' }}">
                        {{ $filled }}/{{ count(\App\Models\AssessmentRecommendation::CONTENT_FIELDS) }} filled
                    </span>

                    @if ($rec && ! $rec->is_active)
                        <span class="badge-neutral shrink-0">Hidden</span>
                    @endif

                    <a href="{{ route('internal.assessment-content.edit', $key) }}" class="btn-secondary btn-sm shrink-0">
                        <i class="ti ti-edit"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
