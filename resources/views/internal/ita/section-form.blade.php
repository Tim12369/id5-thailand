@extends('internal.layouts.app')

@section('title', $section ? 'Edit Section' : 'Create Section')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ $section ? 'Edit Section' : 'Create Section' }}</h2>
        <p class="text-sm text-slate-400 mt-1">Sections group related ITA cards (e.g. "9.1 ข้อมูลพื้นฐาน").</p>
    </div>

    <form method="POST" action="{{ $section ? route('internal.ita.sections.update', $section) : route('internal.ita.sections.store') }}" class="portal-card p-5 max-w-2xl">
        @csrf
        @if ($section)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Section Number <span class="text-red-400">*</span></label>
                <input type="text" name="section_number" value="{{ old('section_number', $section?->section_number) }}" placeholder="9.1" class="portal-input" required>
                @error('section_number') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $section?->sort_order ?? $nextSortOrder) }}" min="0" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Title (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="title_th" value="{{ old('title_th', $section?->title_th) }}" class="portal-input" required>
                @error('title_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Title (English)</label>
                <input type="text" name="title_en" value="{{ old('title_en', $section?->title_en) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Description (Thai)</label>
                <textarea name="description_th" rows="3" class="portal-input">{{ old('description_th', $section?->description_th) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Description (English)</label>
                <textarea name="description_en" rows="3" class="portal-input">{{ old('description_en', $section?->description_en) }}</textarea>
            </div>
        </div>

        <label class="flex items-center gap-2 mt-4 text-sm text-slate-200">
            <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $section?->is_visible ?? true))>
            Visible on public page
        </label>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.ita.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
