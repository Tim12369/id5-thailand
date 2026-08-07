@extends('internal.layouts.app')

@section('title', 'Edit Topic — ' . $topic->topic)

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Topic — {{ $topic->topic }}</h2>
        <p class="text-sm text-slate-400 mt-1">Shared across every persona that uses this topic.</p>
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

    <form method="POST" action="{{ route('internal.smart-guide-topics.update', $topic) }}" class="portal-card p-5 max-w-3xl mb-8">
        @include('internal.smart-guide.topics.form', ['topic' => $topic])
    </form>

    <div class="max-w-3xl space-y-8">
        {{-- ── Required Documents ──────────────────────────────────── --}}
        <div>
            <h3 class="text-sm font-semibold text-white mb-3">Required Documents ({{ $topic->documents->count() }})</h3>
            <div class="portal-card overflow-hidden mb-4">
                <div class="divide-y divide-white/5">
                    @forelse ($topic->documents as $document)
                        <div class="flex items-center gap-4 p-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-100">{{ $document->name_th }}</p>
                                <p class="text-xs text-slate-400">{{ $document->name_en }} @if($document->form) &middot; linked to form {{ $document->form->code }} @endif</p>
                            </div>
                            @unless ($document->is_required)
                                <span class="badge-neutral shrink-0">Optional</span>
                            @endunless
                            <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}?edit_document={{ $document->id }}#document-form" class="btn-secondary btn-sm shrink-0"><i class="ti ti-edit"></i></a>
                            <form method="POST" action="{{ route('internal.smart-guide-topics.documents.destroy', $document) }}" onsubmit="return confirm('Remove this document?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger-ghost btn-sm"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">No documents yet.</div>
                    @endforelse
                </div>
            </div>

            <form id="document-form" method="POST"
                  action="{{ $editDocument ? route('internal.smart-guide-topics.documents.update', $editDocument) : route('internal.smart-guide-topics.documents.store', $topic) }}"
                  class="portal-card p-4">
                @csrf
                @if ($editDocument) @method('PUT') @endif
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $editDocument ? 'Edit Document' : 'Add Document' }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" name="name_th" value="{{ old('name_th', $editDocument?->name_th) }}" placeholder="Name (Thai)" class="portal-input" required>
                    <input type="text" name="name_en" value="{{ old('name_en', $editDocument?->name_en) }}" placeholder="Name (English)" class="portal-input" required>
                    <input type="text" name="name_zh" value="{{ old('name_zh', $editDocument?->name_zh) }}" placeholder="Name (Chinese)" class="portal-input">
                </div>
                <div class="flex items-center gap-4 mt-3">
                    <select name="form_id" class="portal-input flex-1">
                        <option value="">— No linked download form —</option>
                        @foreach ($forms as $form)
                            <option value="{{ $form->id }}" @selected(old('form_id', $editDocument?->form_id) == $form->id)>{{ $form->code }} — {{ $form->name_th }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-slate-200 shrink-0">
                        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $editDocument?->is_required ?? true))>
                        Required
                    </label>
                    <button type="submit" class="btn-portal-primary shrink-0">{{ $editDocument ? 'Update' : 'Add' }}</button>
                    @if ($editDocument)
                        <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}#document-form" class="btn-secondary shrink-0">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Steps ────────────────────────────────────────────────── --}}
        <div>
            <h3 class="text-sm font-semibold text-white mb-3">Steps ({{ $topic->steps->count() }})</h3>
            <div class="portal-card overflow-hidden mb-4">
                <div class="divide-y divide-white/5">
                    @forelse ($topic->steps as $step)
                        <div class="flex items-center gap-4 p-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/10 text-white text-xs flex items-center justify-center">{{ $step->step_number }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-100">{{ $step->title_th }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $step->title_en }}</p>
                            </div>
                            <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}?edit_step={{ $step->id }}#step-form" class="btn-secondary btn-sm shrink-0"><i class="ti ti-edit"></i></a>
                            <form method="POST" action="{{ route('internal.smart-guide-topics.steps.destroy', $step) }}" onsubmit="return confirm('Remove this step?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger-ghost btn-sm"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">No steps yet.</div>
                    @endforelse
                </div>
            </div>

            <form id="step-form" method="POST"
                  action="{{ $editStep ? route('internal.smart-guide-topics.steps.update', $editStep) : route('internal.smart-guide-topics.steps.store', $topic) }}"
                  class="portal-card p-4">
                @csrf
                @if ($editStep) @method('PUT') @endif
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $editStep ? 'Edit Step' : 'Add Step (appended to the end)' }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <input type="text" name="title_th" value="{{ old('title_th', $editStep?->title_th) }}" placeholder="Title (Thai)" class="portal-input" required>
                    <input type="text" name="title_en" value="{{ old('title_en', $editStep?->title_en) }}" placeholder="Title (English)" class="portal-input" required>
                    <input type="text" name="title_zh" value="{{ old('title_zh', $editStep?->title_zh) }}" placeholder="Title (Chinese)" class="portal-input">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <textarea name="description_th" rows="2" placeholder="Description (Thai)" class="portal-input">{{ old('description_th', $editStep?->description_th) }}</textarea>
                    <textarea name="description_en" rows="2" placeholder="Description (English)" class="portal-input">{{ old('description_en', $editStep?->description_en) }}</textarea>
                    <textarea name="description_zh" rows="2" placeholder="Description (Chinese)" class="portal-input">{{ old('description_zh', $editStep?->description_zh) }}</textarea>
                </div>
                <div class="flex items-center gap-3 mt-3">
                    <button type="submit" class="btn-portal-primary">{{ $editStep ? 'Update' : 'Add' }}</button>
                    @if ($editStep)
                        <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}#step-form" class="btn-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Locations ────────────────────────────────────────────── --}}
        <div>
            <h3 class="text-sm font-semibold text-white mb-3">Locations ({{ $topic->locations->count() }})</h3>
            <div class="portal-card overflow-hidden mb-4">
                <div class="divide-y divide-white/5">
                    @forelse ($topic->locations as $location)
                        <div class="flex items-center gap-4 p-3">
                            <span class="badge-info shrink-0">{{ $location->type }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-100">{{ $location->label_th }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $location->label_en }} @if($location->station) &middot; {{ $location->station->name_th }} @endif</p>
                            </div>
                            <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}?edit_location={{ $location->id }}#location-form" class="btn-secondary btn-sm shrink-0"><i class="ti ti-edit"></i></a>
                            <form method="POST" action="{{ route('internal.smart-guide-topics.locations.destroy', $location) }}" onsubmit="return confirm('Remove this location?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger-ghost btn-sm"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">No locations yet.</div>
                    @endforelse
                </div>
            </div>

            <form id="location-form" method="POST"
                  action="{{ $editLocation ? route('internal.smart-guide-topics.locations.update', $editLocation) : route('internal.smart-guide-topics.locations.store', $topic) }}"
                  class="portal-card p-4">
                @csrf
                @if ($editLocation) @method('PUT') @endif
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $editLocation ? 'Edit Location' : 'Add Location' }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-3">
                    <select name="type" class="portal-input" required>
                        @foreach (['office', 'online', 'embassy', 'other'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $editLocation?->type) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="label_th" value="{{ old('label_th', $editLocation?->label_th) }}" placeholder="Label (Thai)" class="portal-input" required>
                    <input type="text" name="label_en" value="{{ old('label_en', $editLocation?->label_en) }}" placeholder="Label (English)" class="portal-input" required>
                    <input type="text" name="label_zh" value="{{ old('label_zh', $editLocation?->label_zh) }}" placeholder="Label (Chinese)" class="portal-input">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="url" name="url" value="{{ old('url', $editLocation?->url) }}" placeholder="https:// (for online locations)" class="portal-input">
                    <select name="station_id" class="portal-input">
                        <option value="">— No linked office —</option>
                        @foreach ($stations as $station)
                            <option value="{{ $station->id }}" @selected(old('station_id', $editLocation?->station_id) == $station->id)>{{ $station->name_th }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-3 mt-3">
                    <button type="submit" class="btn-portal-primary">{{ $editLocation ? 'Update' : 'Add' }}</button>
                    @if ($editLocation)
                        <a href="{{ route('internal.smart-guide-topics.edit', $topic) }}#location-form" class="btn-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
