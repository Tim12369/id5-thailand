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
                <label class="portal-label">Short Message (Thai) <span class="text-red-400">*</span></label>
                <textarea name="excerpt_th" rows="4" class="portal-input" required>{{ old('excerpt_th', $article?->excerpt_th) }}</textarea>
                @error('excerpt_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Short Message (English)</label>
                <textarea name="excerpt_en" rows="4" class="portal-input">{{ old('excerpt_en', $article?->excerpt_en) }}</textarea>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="portal-card p-4 space-y-4">
            <div>
                <label class="portal-label">Alert Type <span class="text-red-400">*</span></label>
                <select name="alert_level" class="portal-input" required>
                    @foreach (['info' => 'Information', 'warning' => 'Warning', 'danger' => 'Emergency'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('alert_level', $article?->alert_level ?? 'info') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('alert_level') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="portal-label">Start Date</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $article?->published_at?->format('Y-m-d\TH:i')) }}" class="portal-input">
            </div>

            <div>
                <label class="portal-label">End Date</label>
                <input type="datetime-local" name="expired_at" value="{{ old('expired_at', $article?->expired_at?->format('Y-m-d\TH:i')) }}" class="portal-input">
                @error('expired_at') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="btn-portal-primary">Save</button>
    <a href="{{ route('internal.cms.alerts.index') }}" class="btn-secondary">Cancel</a>
</div>
