@csrf
@if ($topic)
    @method('PUT')
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="portal-label">Topic slug <span class="text-red-400">*</span></label>
        <input type="text" name="topic" value="{{ old('topic', $topic?->topic) }}" placeholder="e.g. visa, tm30, 90day" class="portal-input" required>
        <p class="text-xs text-slate-500 mt-1">Must match the topic slug used across Smart Guide (lowercase, no spaces).</p>
        @error('topic') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="portal-label">Sort Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $topic?->sort_order) }}" min="0" class="portal-input">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="portal-label">Icon (Tabler name)</label>
        <input type="text" name="icon" value="{{ old('icon', $topic?->icon) }}" placeholder="ti-passport" class="portal-input">
    </div>
    <div>
        <label class="portal-label">Color (hex)</label>
        <input type="color" name="color" value="{{ old('color', $topic?->color ?? '#4F46E5') }}" class="portal-input h-11 p-1">
    </div>
    <div>
        <label class="portal-label">Deadline (days)</label>
        <input type="number" name="deadline_days" value="{{ old('deadline_days', $topic?->deadline_days) }}" min="0" class="portal-input" placeholder="e.g. 90">
    </div>
</div>

<div class="mt-4">
    <label class="portal-label">Search Keywords</label>
    <input type="text" name="search_keywords" value="{{ old('search_keywords', $topic?->search_keywords) }}" placeholder="visa, tourist visa, non-immigrant..." class="portal-input">
    <p class="text-xs text-slate-500 mt-1">Comma-separated terms used by the public Smart Guide search.</p>
</div>

<div class="flex items-center gap-6 mt-4">
    <label class="flex items-center gap-2 text-sm text-slate-200">
        <input type="checkbox" name="is_urgent" value="1" @checked(old('is_urgent', $topic?->is_urgent))>
        Mark as urgent (deadline warning shown to users)
    </label>
    <label class="flex items-center gap-2 text-sm text-slate-200">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $topic?->is_active ?? true))>
        Active
    </label>
</div>

<div class="flex items-center gap-3 mt-6">
    <button type="submit" class="btn-portal-primary">Save</button>
    <a href="{{ route('internal.smart-guide-topics.index') }}" class="btn-secondary">Cancel</a>
</div>
