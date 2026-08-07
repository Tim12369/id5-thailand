@extends('internal.layouts.app')

@section('title', $card ? 'Edit Card' : 'Create Card')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ $card ? 'Edit Card' : 'Create Card' }}</h2>
        <p class="text-sm text-slate-400 mt-1">Cards are the numbered tiles visitors click to read ITA content.</p>
    </div>

    <form method="POST" action="{{ $card ? route('internal.ita.cards.update', $card) : route('internal.ita.cards.store') }}" class="portal-card p-5 max-w-3xl">
        @csrf
        @if ($card)
            @method('PUT')
        @endif

        <div>
            <label class="portal-label">Section <span class="text-red-400">*</span></label>
            <select name="section_id" class="portal-input" required>
                <option value="">— Select a section —</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(old('section_id', $selectedSectionId) == $section->id)>
                        {{ $section->section_number }} — {{ $section->title_th }}
                    </option>
                @endforeach
            </select>
            @error('section_id') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Card Number <span class="text-red-400">*</span></label>
                <input type="text" name="card_number" value="{{ old('card_number', $card?->card_number) }}" placeholder="01" class="portal-input" required>
                @error('card_number') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Accent Color</label>
                <input type="color" name="color" value="{{ old('color', $card?->color ?? '#4F46E5') }}" class="portal-input h-11 p-1">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Title (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="title_th" value="{{ old('title_th', $card?->title_th) }}" class="portal-input" required>
                @error('title_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Title (English)</label>
                <input type="text" name="title_en" value="{{ old('title_en', $card?->title_en) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Content (Thai) <span class="text-red-400">*</span></label>
                <textarea name="content_th" rows="8" class="portal-input" required>{{ old('content_th', $card?->content_th) }}</textarea>
                @error('content_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Content (English)</label>
                <textarea name="content_en" rows="8" class="portal-input">{{ old('content_en', $card?->content_en) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $card?->sort_order ?? $nextSortOrder) }}" min="0" class="portal-input">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $card?->is_visible ?? true))>
                    Visible on public page
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.ita.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
