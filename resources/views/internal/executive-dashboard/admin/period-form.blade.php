@extends('internal.layouts.app')

@section('title', $period ? 'Edit Period' : 'Create Period')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-bold text-white">{{ $period ? 'Edit Period' : 'Create New Period' }}</h2>
    <p class="text-sm text-slate-400 mt-0.5">กำหนดช่วงเวลาสำหรับข้อมูล Executive Dashboard</p>
</div>

<div class="max-w-lg">
    <div class="portal-card p-6">
        <form method="POST" action="{{ $period ? route('internal.executive.admin.update', $period) : route('internal.executive.admin.store') }}">
            @csrf
            @if($period) @method('PUT') @endif

            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="portal-label">Period Type <span class="text-red-400">*</span></label>
                        <select name="period_type" class="portal-input" required>
                            @foreach(['daily' => 'Daily (รายวัน)', 'weekly' => 'Weekly (รายสัปดาห์)', 'monthly' => 'Monthly (รายเดือน)', 'yearly' => 'Yearly (รายปี)'] as $val => $lbl)
                                <option value="{{ $val }}" @selected(old('period_type', $period?->period_type ?? 'monthly') === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('period_type') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="portal-label">Period Date <span class="text-red-400">*</span></label>
                        <input type="date" name="period_date"
                               value="{{ old('period_date', $period?->period_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                               class="portal-input" required>
                        @error('period_date') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="portal-label">Label (Thai)</label>
                        <input type="text" name="label_th"
                               value="{{ old('label_th', $period?->label_th) }}"
                               class="portal-input" placeholder="เช่น มิถุนายน 2569">
                    </div>
                    <div>
                        <label class="portal-label">Label (English)</label>
                        <input type="text" name="label_en"
                               value="{{ old('label_en', $period?->label_en) }}"
                               class="portal-input" placeholder="e.g. June 2026">
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                    <input type="hidden" name="is_latest" value="0">
                    <input type="checkbox" name="is_latest" id="is_latest" value="1"
                           @checked(old('is_latest', $period?->is_latest))
                           class="w-4 h-4 rounded accent-brand-500">
                    <label for="is_latest" class="text-sm text-slate-300 cursor-pointer">
                        Set as active period (shown on dashboard by default)
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-6">
                <button type="submit" class="btn-portal-primary">
                    {{ $period ? 'Update Period' : 'Create Period' }}
                </button>
                <a href="{{ route('internal.executive.admin.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
