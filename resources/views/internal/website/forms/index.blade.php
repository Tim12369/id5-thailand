@extends('internal.layouts.app')

@section('title', 'Download Forms')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Download Forms</h2>
        <p class="text-sm text-slate-400 mt-1">Attach the actual PDF file behind each official form shown on the public Download Forms page.</p>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="space-y-6">
        @forelse ($forms as $category => $categoryForms)
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                    {{ $categories[$category] ?? $category }}
                </h3>
                <div class="portal-card overflow-hidden">
                    <div class="divide-y divide-white/5">
                        @foreach ($categoryForms as $form)
                            <div class="flex items-center gap-4 p-4">
                                <span class="shrink-0 text-xs font-bold text-blue-300 bg-blue-500/10 border border-blue-400/20 rounded-lg px-2 py-1">
                                    {{ $form->code }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-100 truncate">{{ $form->name_th }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ $form->name_en }} &middot; v{{ $form->version }}</p>
                                </div>

                                @if ($form->file_exists)
                                    <span class="badge-success shrink-0"><i class="ti ti-file-check mr-1"></i>File attached</span>
                                @else
                                    <span class="badge-danger shrink-0"><i class="ti ti-file-off mr-1"></i>No file uploaded</span>
                                @endif

                                @unless ($form->is_active)
                                    <span class="badge-neutral shrink-0">Hidden</span>
                                @endunless

                                <a href="{{ route('internal.website-forms.edit', $form) }}" class="btn-secondary btn-sm shrink-0">
                                    <i class="ti ti-edit"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="portal-card p-8 text-center text-sm text-slate-400">No forms found.</div>
        @endforelse
    </div>
@endsection
