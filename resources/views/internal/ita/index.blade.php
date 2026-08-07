@extends('internal.layouts.app')

@section('title', 'ITA Management')

@section('content')
    @php($canManage = auth()->user()->hasPermission('ita.manage'))

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">ITA Management</h2>
            <p class="text-sm text-slate-400 mt-1">Manage the sections and cards shown on the public Integrity &amp; Transparency Assessment page.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('internal.ita.sections.create') }}" class="btn-portal-primary">
                <i class="ti ti-plus"></i> New Section
            </a>
        @endif
    </div>

    <div class="space-y-6">
        @forelse ($sections as $section)
            <div class="portal-card overflow-hidden">
                <div class="flex items-center justify-between gap-4 p-4 border-b border-white/10">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="badge-info shrink-0">{{ $section->section_number }}</span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-100 truncate">{{ $section->title_th }}</p>
                            @if ($section->title_en)
                                <p class="text-xs text-slate-400 truncate">{{ $section->title_en }}</p>
                            @endif
                        </div>
                        @unless ($section->is_visible)
                            <span class="badge-neutral shrink-0">Hidden</span>
                        @endunless
                    </div>

                    @if ($canManage)
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('internal.ita.cards.create', ['section_id' => $section->id]) }}" class="btn-secondary btn-sm">
                                <i class="ti ti-plus"></i> Card
                            </a>
                            <a href="{{ route('internal.ita.sections.edit', $section) }}" class="btn-secondary btn-sm">
                                <i class="ti ti-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('internal.ita.sections.destroy', $section) }}" onsubmit="return confirm('Delete this section and all of its cards?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-ghost btn-sm">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="divide-y divide-white/5">
                    @forelse ($section->cards as $card)
                        <div class="flex items-center gap-4 p-4">
                            <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-xs font-bold text-white"
                                  style="background-color: {{ $card->color ?? '#4F46E5' }}">
                                {{ $card->card_number }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-100 truncate">{{ $card->title_th }}</p>
                                @if ($card->title_en)
                                    <p class="text-xs text-slate-400 truncate">{{ $card->title_en }}</p>
                                @endif
                            </div>

                            @unless ($card->is_visible)
                                <span class="badge-neutral shrink-0">Hidden</span>
                            @endunless

                            @if ($canManage)
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('internal.ita.cards.edit', $card) }}" class="btn-secondary btn-sm">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('internal.ita.cards.destroy', $card) }}" onsubmit="return confirm('Delete this card?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger-ghost btn-sm">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-slate-400">No cards in this section yet.</div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="portal-card p-8 text-center text-sm text-slate-400">
                No sections yet.
                @if ($canManage)
                    <a href="{{ route('internal.ita.sections.create') }}" class="text-gold-500 hover:underline">Create the first one.</a>
                @endif
            </div>
        @endforelse
    </div>
@endsection
