@extends('internal.layouts.app')

@section('title', 'About Us Documents')

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">About Us Documents</h2>
            <p class="text-sm text-slate-400 mt-1">Manage the downloadable documents box shown on the public About Us page.</p>
        </div>
        <a href="{{ route('internal.website-about-documents.create') }}" class="btn-portal-primary shrink-0">
            <i class="ti ti-plus mr-1"></i> Add Document
        </a>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($documents as $document)
                <div class="flex items-center gap-4 p-4">
                    <div class="w-10 h-10 rounded-lg bg-red-500/10 text-red-400 flex items-center justify-center shrink-0">
                        <i class="ti ti-file-type-pdf text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $document->name_th }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $document->name_en }}</p>
                    </div>

                    @if ($document->file_exists)
                        <span class="badge-success shrink-0"><i class="ti ti-file-check mr-1"></i>File attached</span>
                    @else
                        <span class="badge-danger shrink-0"><i class="ti ti-file-off mr-1"></i>No file uploaded</span>
                    @endif

                    @unless ($document->is_active)
                        <span class="badge-neutral shrink-0">Hidden</span>
                    @endunless

                    <a href="{{ route('internal.website-about-documents.edit', $document) }}" class="btn-secondary btn-sm shrink-0">
                        <i class="ti ti-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('internal.website-about-documents.destroy', $document) }}" onsubmit="return confirm('Remove this document?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-ghost btn-sm shrink-0"><i class="ti ti-trash"></i></button>
                    </form>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No documents yet. Add one to show it on the public About Us page.</div>
            @endforelse
        </div>
    </div>
@endsection
