@php
    $s = $station;
    // Thailand's regions, as already used for public filtering (Station::region).
    $regionOptions = ['ภาคเหนือ', 'ภาคกลาง', 'ภาคตะวันออก', 'ภาคตะวันออกเฉียงเหนือ', 'ภาคใต้'];
    $provinceOptions = \App\Models\Station::query()->distinct()->orderBy('province')->pluck('province')->filter();
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="portal-label">Location Name (Thai) <span class="text-red-400">*</span></label>
        <input type="text" name="name_th" value="{{ old('name_th', $s?->name_th) }}" class="portal-input" required>
        @error('name_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="portal-label">Location Name (English)</label>
        <input type="text" name="name_en" value="{{ old('name_en', $s?->name_en) }}" class="portal-input" placeholder="e.g. Chiang Mai Immigration Office">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="portal-label">Exact Address (Thai) <span class="text-red-400">*</span></label>
        <textarea name="address_th" rows="2" class="portal-input" required>{{ old('address_th', $s?->address_th) }}</textarea>
        @error('address_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="portal-label">Exact Address (English)</label>
        <textarea name="address_en" rows="2" class="portal-input" placeholder="e.g. 71 Sanphitak Rd, Chang Phueak, Mueang, Chiang Mai 50300">{{ old('address_en', $s?->address_en) }}</textarea>
    </div>
</div>

<p class="text-xs text-slate-500 mt-2">
    <i class="ti ti-map-pin"></i> The map location is found automatically from this address when you save — no coordinates needed.
</p>

<div class="mt-4">
    <label class="portal-label">Google Maps Link (optional)</label>
    <input type="url" name="google_maps_url" value="{{ old('google_maps_url', $s?->getRawOriginal('google_maps_url')) }}"
           class="portal-input" placeholder="https://maps.app.goo.gl/... or https://www.google.com/maps/place/...">
    <p class="text-xs text-slate-500 mt-1">
        Paste a link straight from Google Maps (e.g. its "Share" button) and "Get Directions" on the public site will go exactly there. Leave blank to use the address above instead.
    </p>
    @error('google_maps_url') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="portal-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $s?->phone) }}" class="portal-input">
    </div>
    <div>
        <label class="portal-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $s?->email) }}" class="portal-input">
    </div>
</div>

<div class="mt-4">
    <label class="portal-label">Official Website</label>
    <input type="url" name="website" value="{{ old('website', $s?->website) }}" class="portal-input" placeholder="https://...">
    @error('website') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="portal-label">Region <span class="text-red-400">*</span></label>
        <select name="region" class="portal-input" required>
            <option value="">— Select region —</option>
            @foreach ($regionOptions as $option)
                <option value="{{ $option }}" @selected(old('region', $s?->region) === $option)>{{ $option }}</option>
            @endforeach
        </select>
        @error('region') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="portal-label">Province <span class="text-red-400">*</span></label>
        <input type="text" name="province" value="{{ old('province', $s?->province) }}" class="portal-input" list="province-options" required>
        <datalist id="province-options">
            @foreach ($provinceOptions as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
        @error('province') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<label class="flex items-center gap-2 mt-4 text-sm text-slate-200">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $s?->is_active ?? true))>
    Show on public directory
</label>
