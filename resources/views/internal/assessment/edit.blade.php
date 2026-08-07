@extends('internal.layouts.app')

@section('title', 'Edit Assessment Content — ' . ($def['label']['en'] ?? $status))

@php
    $fieldLabels = [
        'legal_basis'           => ['Legal Basis', null],
        'validity_note'         => ['Validity', null],
        'allowed_activities'    => ['What You Can Do', 'One item per line — each line becomes a bullet.'],
        'restricted_activities' => ['What You Cannot Do', 'One item per line — each line becomes a bullet.'],
        'deadlines'             => ['Important Deadlines', 'Leave blank to use the linked topic\'s deadline automatically.'],
        'responsible_agency'    => ['Responsible Agency', null],
        'important_notices'     => ['Important Notices', null],
    ];
    $selectedTopics = old('suggested_topics', $rec->suggested_topics ?? ($def['topics'] ?? []));
@endphp

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-white/5 text-slate-200 flex items-center justify-center shrink-0">
            <i class="ti {{ $def['icon'] ?? 'ti-user' }} text-xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">{{ $def['label']['en'] ?? $status }}</h2>
            <p class="text-sm text-slate-400">{{ $def['label']['th'] ?? '' }} — guided assessment result content</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.assessment-content.update', $status) }}" class="space-y-5 max-w-4xl">
        @csrf
        @method('PUT')

        {{-- Meta --}}
        <div class="portal-card p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="portal-label">Risk Level</label>
                    <select name="risk_level" class="portal-input">
                        @foreach (['info' => 'Informational', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('risk_level', $rec->risk_level ?? 'info') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="portal-label">Deep-dive persona</label>
                    <select name="suggested_persona" class="portal-input">
                        <option value="">— None —</option>
                        @foreach ($personas as $p)
                            <option value="{{ $p }}" @selected(old('suggested_persona', $rec->suggested_persona ?? ($def['persona'] ?? '')) === $p)>{{ __("personas.{$p}") }} ({{ $p }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="portal-label">Visibility</label>
                    <label class="flex items-center gap-2 mt-2.5 text-sm text-slate-200">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rec->is_active ?? true))>
                        Show this result content publicly
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <label class="portal-label">Recommended next-step topics</label>
                <div class="flex flex-wrap gap-2 mt-1">
                    @foreach ($topics as $t)
                        <label class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-slate-200 cursor-pointer hover:bg-white/10">
                            <input type="checkbox" name="suggested_topics[]" value="{{ $t }}" @checked(in_array($t, (array) $selectedTopics, true))>
                            {{ __("topics.{$t}") }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Localized content groups --}}
        @foreach ($contentFields as $field)
            @php [$label, $hint] = $fieldLabels[$field] ?? [ucfirst(str_replace('_', ' ', $field)), null]; @endphp
            <div class="portal-card p-5">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wide">{{ $label }}</h3>
                    @if ($hint)<p class="text-xs text-slate-400 mt-0.5">{{ $hint }}</p>@endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach (['th' => 'Thai', 'en' => 'English', 'zh' => 'Chinese'] as $loc => $locLabel)
                        <div>
                            <label class="portal-label">{{ $locLabel }}</label>
                            <textarea name="{{ $field }}_{{ $loc }}" rows="3" class="portal-input">{{ old("{$field}_{$loc}", $rec->{"{$field}_{$loc}"}) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.assessment-content.index') }}" class="btn-secondary">Back to list</a>
        </div>
    </form>
@endsection
