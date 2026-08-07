@extends('internal.layouts.app')

@section('title', 'Site Settings')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <i class="ti ti-settings text-gold-500"></i> Site Settings
        </h1>
        <p class="text-slate-400 text-sm mt-0.5">
            Manage public-facing branding and homepage content.
        </p>
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

    {{-- ── Homepage Hero Image ─────────────────────────────────────── --}}
    <div class="portal-card p-6" x-data="{ preview: null, fileName: null }">
        <h2 class="text-sm font-semibold text-white flex items-center gap-2 mb-1">
            <i class="ti ti-photo text-gold-500"></i> Homepage Hero Image
        </h2>
        <p class="text-xs text-slate-400 mb-4">
            The background photo shown behind the headline on the public homepage.
            Recommended size: 1920×1080px or larger, JPG/PNG/WebP, up to 8MB.
        </p>

        {{-- Current image preview --}}
        <div class="rounded-xl overflow-hidden border border-white/10 mb-4 relative aspect-[16/7] bg-black/30">
            <img :src="preview || '{{ $heroImageUrl }}'" alt="Hero image preview" class="w-full h-full object-cover">
            <span class="absolute top-2 left-2 text-[11px] font-semibold px-2 py-1 rounded-full"
                  :class="preview ? 'bg-gold-500 text-brand-900' : '{{ $hasCustomHeroImage ? "bg-emerald-500/90 text-white" : "bg-white/20 text-white" }}'"
                  x-text="preview ? 'New image (preview)' : '{{ $hasCustomHeroImage ? "Current custom image" : "Default image" }}'"></span>
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('internal.settings.hero-image.update') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <label class="block">
                <span class="portal-label">Replace image</span>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" required
                       class="portal-input"
                       @change="
                           const file = $event.target.files[0];
                           fileName = file ? file.name : null;
                           if (file) { preview = URL.createObjectURL(file); } else { preview = null; }
                       ">
            </label>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-upload"></i> Upload &amp; Save
                </button>
                <span class="text-xs text-slate-500" x-show="fileName" x-text="fileName"></span>
            </div>
        </form>

        {{-- Remove / reset to default --}}
        @if ($hasCustomHeroImage)
        <form method="POST" action="{{ route('internal.settings.hero-image.destroy') }}" class="mt-3"
              onsubmit="return confirm('Remove the custom hero image and revert to the default photo?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-portal-secondary text-sm">
                <i class="ti ti-trash"></i> Remove &amp; Reset to Default
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
