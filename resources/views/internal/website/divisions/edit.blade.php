@extends('internal.layouts.app')

@section('title', 'Edit Division — ' . $card->title_th)

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Division Card</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $card->title_th }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-divisions.update', $card) }}" enctype="multipart/form-data" class="portal-card p-5 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-4 mb-5">
            <div class="shrink-0 w-28 h-20 rounded-xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center">
                @if ($card->photo_url)
                    <img src="{{ $card->photo_url }}" alt="" class="w-full h-full object-cover">
                @else
                    <i class="ti ti-photo-off text-2xl text-slate-500"></i>
                @endif
            </div>
            <div class="flex-1">
                <label class="portal-label">Photo</label>
                <input type="file" name="photo" accept="image/*" class="portal-input">
                <p class="text-xs text-slate-500 mt-1">JPG, PNG, or WEBP, up to 4MB. Leave empty to keep the current photo. If no photo is set, the card falls back to a colored icon tile on the public homepage.</p>
                @error('photo') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Title (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="title_th" value="{{ old('title_th', $card->title_th) }}" class="portal-input" required>
                @error('title_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Title (English)</label>
                <input type="text" name="title_en" value="{{ old('title_en', $card->title_en) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Description (Thai)</label>
                <input type="text" name="desc_th" value="{{ old('desc_th', $card->desc_th) }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">Description (English)</label>
                <input type="text" name="desc_en" value="{{ old('desc_en', $card->desc_en) }}" class="portal-input">
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.website-divisions.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
