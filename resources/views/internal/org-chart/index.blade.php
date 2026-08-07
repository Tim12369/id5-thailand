@extends('internal.layouts.app')

@section('title', 'Organization Chart')

@section('content')
@php
    $sectionsPayload = $sections->map(fn ($s) => [
        'id' => $s->id, 'slug' => $s->slug, 'name_th' => $s->name_th, 'theme' => $s->theme,
    ])->values();
@endphp
<div class="p-6 max-w-400 mx-auto space-y-6"
     x-data="orgChartAdmin(@js(['sections' => $sectionsPayload, 'canManage' => $canManage]))">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="ti ti-sitemap text-gold-500"></i> Organization Chart
            </h1>
            <p class="text-slate-400 text-sm mt-0.5">
                Manage the people and photos shown on the public About Us page.
            </p>
        </div>
        <a href="{{ route('public.about') }}" target="_blank" class="btn-portal-secondary text-sm">
            <i class="ti ti-external-link"></i> View Public Page
        </a>
    </div>

    @if (session('success'))
    <div class="badge-success inline-flex">
        <i class="ti ti-check mr-1"></i> {{ session('success') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── Section Tabs ─────────────────────────────────────────────── --}}
    <div class="flex gap-1 bg-white/5 rounded-xl p-1 w-fit">
        @foreach ($sections as $section)
        <button type="button" @click="activeSection = '{{ $section->slug }}'"
                :class="activeSection === '{{ $section->slug }}' ? 'bg-white/10 text-gold-500 font-semibold' : 'text-slate-400'"
                class="px-4 py-2 rounded-lg text-sm transition-colors">
            {{ $section->name_th }}
        </button>
        @endforeach
    </div>

    {{-- ── Per-Section Member Management ───────────────────────────── --}}
    @foreach ($sections as $section)
    <div x-show="activeSection === '{{ $section->slug }}'" class="space-y-4">

        @foreach ($section->tiers as $tier)
        <div class="portal-card p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-white">{{ $tier->label_th }}</h3>
                <p class="text-xs text-slate-500">{{ $tier->members->count() }} member(s)</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach ($tier->members as $member)
                <div class="rounded-xl border border-white/10 p-3 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-white/5 border border-white/10 overflow-hidden flex items-center justify-center mb-2">
                        @if ($member->photo_url)
                            <img src="{{ $member->photo_url }}" alt="" class="w-full h-full object-cover">
                        @else
                            <i class="ti ti-user text-2xl text-slate-500"></i>
                        @endif
                    </div>
                    <p class="text-xs font-medium text-slate-200 truncate" title="{{ $member->name_th }}">{{ $member->name_th }}</p>
                    @if ($canManage)
                    <div class="flex items-center justify-center gap-2 mt-2">
                        <button type="button" class="text-[11px] text-brand-400 hover:text-brand-300"
                                @click="openMemberModal('edit', {{ $member->id }}, {{ $tier->id }}, @js($member->name_th), @js($member->name_en ?? ''), @js($member->rank_th ?? ''), @js($member->position_th ?? ''), @js($member->photo_url ?? ''))">
                            Edit
                        </button>
                        <form method="POST" action="{{ route('internal.org-chart.members.destroy', $member) }}"
                              onsubmit="return confirm('Remove this member?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] text-red-400 hover:text-red-300">Remove</button>
                        </form>
                    </div>
                    @endif
                </div>
                @endforeach

                @if ($canManage)
                <button type="button" @click="openMemberModal('create', null, {{ $tier->id }})"
                        class="rounded-xl border border-dashed border-white/15 p-3 flex flex-col items-center justify-center text-slate-500 hover:text-gold-500 hover:border-gold-500/40 transition-colors min-h-[112px]">
                    <i class="ti ti-plus text-xl mb-1"></i>
                    <span class="text-[11px]">Add Member</span>
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    {{-- ── Member Modal (Create/Edit) ──────────────────────────────── --}}
    <div x-show="memberModalOpen" x-cloak class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="memberModalOpen = false" style="display:none">
        <div class="portal-card p-6 max-w-md w-full max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white" x-text="memberForm.mode === 'create' ? 'Add Member' : 'Edit Member'"></h3>
                <button @click="memberModalOpen = false" class="text-slate-400 hover:text-white"><i class="ti ti-x"></i></button>
            </div>
            <form method="POST" :action="memberForm.id ? '{{ url('internal/org-chart/members') }}/' + memberForm.id : '{{ route('internal.org-chart.members.store') }}'"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="tier_id" :value="memberForm.tier_id">

                <div x-show="memberForm.photo_url" class="flex items-center gap-3">
                    <img :src="memberForm.photo_url" alt="" class="w-16 h-16 rounded-full object-cover border border-white/10">
                    <span class="text-xs text-slate-500">Current photo</span>
                </div>

                <div>
                    <label class="portal-label">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="portal-input">
                </div>
                <div>
                    <label class="portal-label">Name (Thai) <span class="text-red-400">*</span></label>
                    <input type="text" name="name_th" x-model="memberForm.name_th" class="portal-input" required>
                </div>
                <div>
                    <label class="portal-label">Name (English)</label>
                    <input type="text" name="name_en" x-model="memberForm.name_en" class="portal-input">
                </div>
                <div>
                    <label class="portal-label">Rank</label>
                    <input type="text" name="rank_th" x-model="memberForm.rank_th" class="portal-input" placeholder="e.g. พล.ต.อ.">
                </div>
                <div>
                    <label class="portal-label">Position / Title</label>
                    <input type="text" name="position_th" x-model="memberForm.position_th" class="portal-input">
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-portal-primary flex-1 justify-center">Save Member</button>
                    <button type="button" @click="memberModalOpen = false" class="btn-portal-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orgChartAdmin', (initial) => ({
        activeSection: initial.sections.length ? initial.sections[0].slug : null,
        canManage: initial.canManage,
        memberModalOpen: false,
        memberForm: { mode: 'create', id: null, tier_id: null, name_th: '', name_en: '', rank_th: '', position_th: '', photo_url: '' },

        openMemberModal(mode, id, tierId, nameTh = '', nameEn = '', rankTh = '', positionTh = '', photoUrl = '') {
            this.memberForm = { mode, id, tier_id: tierId, name_th: nameTh, name_en: nameEn, rank_th: rankTh, position_th: positionTh, photo_url: photoUrl };
            this.memberModalOpen = true;
        },
    }));
});
</script>
@endpush
