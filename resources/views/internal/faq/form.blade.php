@extends('internal.layouts.app')

@section('title', $faq ? 'Edit FAQ' : 'Add FAQ')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ $faq ? 'Edit FAQ' : 'Add FAQ' }}</h2>
        <p class="text-sm text-slate-400 mt-1">Shown on the public FAQ page, grouped by category.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $faq ? route('internal.faq.update', $faq) : route('internal.faq.store') }}" class="portal-card p-5 max-w-3xl">
        @csrf
        @if ($faq)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Category</label>
                <select name="category_id" class="portal-input">
                    <option value="">— None —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $faq?->category_id) == $category->id)>
                            {{ $category->name_en ?: $category->name_th }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="portal-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $faq?->sort_order) }}" min="0" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="portal-label">Question (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="question_th" value="{{ old('question_th', $faq?->question_th) }}" class="portal-input" required>
                @error('question_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Question (English)</label>
                <input type="text" name="question_en" value="{{ old('question_en', $faq?->question_en) }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">Question (Chinese)</label>
                <input type="text" name="question_zh" value="{{ old('question_zh', $faq?->question_zh) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="portal-label">Answer (Thai) <span class="text-red-400">*</span></label>
                <textarea name="answer_th" rows="6" class="portal-input" required>{{ old('answer_th', $faq?->answer_th) }}</textarea>
                @error('answer_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Answer (English)</label>
                <textarea name="answer_en" rows="6" class="portal-input">{{ old('answer_en', $faq?->answer_en) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Answer (Chinese)</label>
                <textarea name="answer_zh" rows="6" class="portal-input">{{ old('answer_zh', $faq?->answer_zh) }}</textarea>
            </div>
        </div>

        <div class="flex items-center gap-6 mt-4">
            <label class="flex items-center gap-2 text-sm text-slate-200">
                <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $faq?->is_public ?? true))>
                Show on public FAQ page
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-200">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq?->is_active ?? true))>
                Active
            </label>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.faq.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
