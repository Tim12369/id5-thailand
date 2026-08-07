@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Title (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="title_th" value="{{ old('title_th', $article?->title_th) }}" class="portal-input" required>
                @error('title_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Title (English)</label>
                <input type="text" name="title_en" value="{{ old('title_en', $article?->title_en) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Summary (Thai)</label>
                <textarea name="excerpt_th" rows="3" class="portal-input">{{ old('excerpt_th', $article?->excerpt_th) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Summary (English)</label>
                <textarea name="excerpt_en" rows="3" class="portal-input">{{ old('excerpt_en', $article?->excerpt_en) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Content (Thai) <span class="text-red-400">*</span></label>
                <textarea name="body_th" rows="10" class="portal-input" required>{{ old('body_th', $article?->body_th) }}</textarea>
                @error('body_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Content (English)</label>
                <textarea name="body_en" rows="10" class="portal-input">{{ old('body_en', $article?->body_en) }}</textarea>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="portal-card p-4">
            <label class="portal-label">Featured Image</label>
            @if ($article?->thumbnail_url)
                <img src="{{ $article->thumbnail_url }}" alt="" class="w-full h-32 object-cover rounded-xl mb-3">
            @endif
            <input type="file" name="thumbnail" accept="image/*" class="portal-input">
            @error('thumbnail') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="portal-card p-4 space-y-4">
            <div>
                <label class="portal-label">Category</label>
                <select name="category_id" class="portal-input">
                    <option value="">— None —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $article?->category_id) == $category->id)>
                            {{ $category->getLocalizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="portal-label">Publish Date</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $article?->published_at?->format('Y-m-d\TH:i')) }}" class="portal-input">
            </div>

            <div>
                <label class="portal-label">Status</label>
                <select name="status" class="portal-input">
                    <option value="draft" @selected(old('status', $article?->status ?? 'draft') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $article?->status) === 'published')>Published</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="btn-portal-primary">Save</button>
    <a href="{{ route('internal.cms.knowledge-base.index') }}" class="btn-secondary">Cancel</a>
</div>
