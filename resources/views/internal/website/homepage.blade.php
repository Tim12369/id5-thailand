@extends('internal.layouts.app')

@section('title', 'Homepage')

@section('content')
    @php($canManage = auth()->user()->hasPermission('website.manage'))

    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Homepage</h2>
        <p class="text-sm text-slate-400 mt-1">
            Hero text shown on the public homepage. Leave a field blank to use the site default.
            The background photo is managed under <a href="{{ route('internal.settings.edit') }}" class="text-gold-500 hover:underline">Hero Images</a>.
        </p>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-homepage.update') }}" class="portal-card p-5 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Hero title (Thai)</label>
                <textarea name="hero_title_th" rows="2" class="portal-input" placeholder="{{ $defaults['hero_title_th'] }}">{{ old('hero_title_th', $values['hero_title_th']) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Hero title (English)</label>
                <textarea name="hero_title_en" rows="2" class="portal-input" placeholder="{{ $defaults['hero_title_en'] }}">{{ old('hero_title_en', $values['hero_title_en']) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Hero subtitle (Thai)</label>
                <textarea name="hero_subtitle_th" rows="2" class="portal-input" placeholder="{{ $defaults['hero_subtitle_th'] }}">{{ old('hero_subtitle_th', $values['hero_subtitle_th']) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Hero subtitle (English)</label>
                <textarea name="hero_subtitle_en" rows="2" class="portal-input" placeholder="{{ $defaults['hero_subtitle_en'] }}">{{ old('hero_subtitle_en', $values['hero_subtitle_en']) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">CTA button label (Thai)</label>
                <input type="text" name="hero_cta_th" value="{{ old('hero_cta_th', $values['hero_cta_th']) }}" placeholder="{{ $defaults['hero_cta_th'] }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">CTA button label (English)</label>
                <input type="text" name="hero_cta_en" value="{{ old('hero_cta_en', $values['hero_cta_en']) }}" placeholder="{{ $defaults['hero_cta_en'] }}" class="portal-input">
            </div>
        </div>

        @if ($canManage)
            <button type="submit" class="btn-portal-primary mt-6">Save</button>
        @endif
    </form>
@endsection
