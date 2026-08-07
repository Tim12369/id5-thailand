@extends('internal.layouts.app')

@section('title', 'Edit Form — ' . $form->code)

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Form — {{ $form->code }}</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $form->name_th }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-forms.update', $form) }}" enctype="multipart/form-data" class="portal-card p-5 max-w-2xl">
        @csrf
        @method('PUT')

        {{-- Current file status --}}
        <div class="rounded-xl border {{ $form->file_exists ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-500/10' }} px-4 py-3 mb-5 flex items-center gap-3">
            <i class="ti {{ $form->file_exists ? 'ti-file-check text-emerald-400' : 'ti-file-off text-red-400' }} text-lg shrink-0"></i>
            <div class="text-sm {{ $form->file_exists ? 'text-emerald-300' : 'text-red-300' }}">
                @if ($form->file_exists)
                    A PDF is currently attached ({{ $form->formatted_size }}). Uploading a new one below will replace it.
                @else
                    No PDF is attached yet — this form's download button will 404 on the public site until one is uploaded.
                @endif
            </div>
        </div>

        <div>
            <label class="portal-label">Replace PDF file @if(!$form->file_exists)<span class="text-red-400">*</span>@endif</label>
            <input type="file" name="file" accept="application/pdf" class="portal-input">
            @error('file') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Name (Thai) <span class="text-red-400">*</span></label>
                <input type="text" name="name_th" value="{{ old('name_th', $form->name_th) }}" class="portal-input" required>
                @error('name_th') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Name (English)</label>
                <input type="text" name="name_en" value="{{ old('name_en', $form->name_en) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Description (Thai)</label>
                <textarea name="description_th" rows="3" class="portal-input">{{ old('description_th', $form->description_th) }}</textarea>
            </div>
            <div>
                <label class="portal-label">Description (English)</label>
                <textarea name="description_en" rows="3" class="portal-input">{{ old('description_en', $form->description_en) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="portal-label">Category <span class="text-red-400">*</span></label>
                <select name="category" class="portal-input" required>
                    @foreach ($categories as $slug => $label)
                        <option value="{{ $slug }}" @selected(old('category', $form->category) === $slug)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Version <span class="text-red-400">*</span></label>
                <input type="text" name="version" value="{{ old('version', $form->version) }}" class="portal-input" required>
                @error('version') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="portal-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $form->sort_order) }}" min="0" class="portal-input">
            </div>
        </div>

        <label class="flex items-center gap-2 mt-4 text-sm text-slate-200">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form->is_active))>
            Show on public Download Forms page
        </label>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.website-forms.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
