@if ($document)
    @method('PUT')

    {{-- Current file status --}}
    <div class="rounded-xl border {{ $document->file_exists ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-500/10' }} px-4 py-3 mb-5 flex items-center gap-3">
        <i class="ti {{ $document->file_exists ? 'ti-file-check text-emerald-400' : 'ti-file-off text-red-400' }} text-lg shrink-0"></i>
        <div class="text-sm {{ $document->file_exists ? 'text-emerald-300' : 'text-red-300' }}">
            @if ($document->file_exists)
                A PDF is currently attached ({{ $document->formatted_size }}). Uploading a new one below will replace it.
            @else
                No PDF is attached yet — this document's download button will 404 on the public site until one is uploaded.
            @endif
        </div>
    </div>
@endif

<div>
    <label class="portal-label">{{ $document ? 'Replace PDF file' : 'PDF file' }} @if(!$document || !$document->file_exists)<span class="text-red-400">*</span>@endif</label>
    <input type="file" name="file" accept="application/pdf" class="portal-input" @if(!$document) required @endif>
    @error('file') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="portal-label">Name (Thai) <span class="text-red-400">*</span></label>
        <input type="text" name="name_th" value="{{ old('name_th', $document?->name_th) }}" class="portal-input" required>
        @error('name_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="portal-label">Name (English)</label>
        <input type="text" name="name_en" value="{{ old('name_en', $document?->name_en) }}" class="portal-input">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="portal-label">Description (Thai)</label>
        <textarea name="description_th" rows="3" class="portal-input">{{ old('description_th', $document?->description_th) }}</textarea>
    </div>
    <div>
        <label class="portal-label">Description (English)</label>
        <textarea name="description_en" rows="3" class="portal-input">{{ old('description_en', $document?->description_en) }}</textarea>
    </div>
</div>

<div class="mt-4">
    <label class="portal-label">Sort Order</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', $document?->sort_order) }}" min="0" class="portal-input max-w-[10rem]">
</div>

<label class="flex items-center gap-2 mt-4 text-sm text-slate-200">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $document?->is_active ?? true))>
    Show on public About Us page
</label>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="btn-portal-primary">Save</button>
    <a href="{{ route('internal.website-about-documents.index') }}" class="btn-secondary">Cancel</a>
</div>
