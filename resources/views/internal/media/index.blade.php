@extends('internal.layouts.app')

@section('title', 'Media Library')

@section('content')
    @php($canManage = auth()->user()->hasPermission('media.manage'))

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Media Library</h2>
            <p class="text-sm text-slate-400 mt-1">Upload once, reuse everywhere across the website.</p>
        </div>
        @if ($canManage)
            <button type="button" class="btn-portal-primary" onclick="document.getElementById('upload-modal').classList.remove('hidden')">
                <i class="ti ti-upload"></i> Upload
            </button>
        @endif
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Sidebar: folders + categories + tags --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="portal-card p-4">
                <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide mb-2">Type</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('internal.media-library.index') }}"
                       class="portal-nav-sublink {{ !$currentCategory ? 'portal-nav-sublink-active' : '' }}">All files</a>
                    <a href="{{ route('internal.media-library.index', ['category' => 'image']) }}"
                       class="portal-nav-sublink {{ $currentCategory === 'image' ? 'portal-nav-sublink-active' : '' }}">Images</a>
                    <a href="{{ route('internal.media-library.index', ['category' => 'video']) }}"
                       class="portal-nav-sublink {{ $currentCategory === 'video' ? 'portal-nav-sublink-active' : '' }}">Videos</a>
                    <a href="{{ route('internal.media-library.index', ['category' => 'document']) }}"
                       class="portal-nav-sublink {{ $currentCategory === 'document' ? 'portal-nav-sublink-active' : '' }}">Documents</a>
                </div>
            </div>

            <div class="portal-card p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide">Folders</p>
                    @if ($canManage)
                        <button type="button" class="text-gold-500 text-xs" onclick="document.getElementById('folder-modal').classList.remove('hidden')">
                            <i class="ti ti-plus"></i>
                        </button>
                    @endif
                </div>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('internal.media-library.index') }}"
                       class="portal-nav-sublink {{ !$currentFolderId ? 'portal-nav-sublink-active' : '' }}">All folders</a>
                    @foreach ($folders as $folder)
                        <a href="{{ route('internal.media-library.index', ['folder_id' => $folder->id]) }}"
                           class="portal-nav-sublink {{ $currentFolderId === $folder->id ? 'portal-nav-sublink-active' : '' }}">
                            <i class="ti ti-folder text-xs mr-1"></i>{{ $folder->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($tags->isNotEmpty())
                <div class="portal-card p-4">
                    <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide mb-2">Tags</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($tags as $tag)
                            <a href="{{ route('internal.media-library.index', ['tag_id' => $tag->id]) }}"
                               class="badge-{{ $currentTagId === $tag->id ? 'info' : 'neutral' }}">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Media grid --}}
        <div class="lg:col-span-3">
            <form method="GET" action="{{ route('internal.media-library.index') }}" class="mb-4">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search files..." class="portal-input max-w-sm">
            </form>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse ($media as $item)
                    <div class="portal-card overflow-hidden">
                        <div class="aspect-square bg-black/30 flex items-center justify-center overflow-hidden">
                            @if ($item->category() === 'image')
                                <img src="{{ $item->url() }}" alt="{{ $item->alt_text ?? $item->original_name }}" class="w-full h-full object-cover">
                            @elseif ($item->category() === 'video')
                                <i class="ti ti-video text-4xl text-slate-500"></i>
                            @else
                                <i class="ti ti-file-text text-4xl text-slate-500"></i>
                            @endif
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-slate-200 truncate" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $item->formattedSize() }}</p>
                            <div class="flex items-center justify-between mt-2">
                                <a href="{{ $item->url() }}" target="_blank" class="btn-secondary btn-sm">
                                    <i class="ti ti-eye"></i>
                                </a>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('internal.media-library.destroy', $item) }}" onsubmit="return confirm('Delete this file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger-ghost btn-sm">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full portal-card p-8 text-center text-sm text-slate-400">
                        No media found.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $media->links() }}</div>
        </div>
    </div>

    {{-- Upload modal --}}
    <div id="upload-modal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div class="portal-card p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Upload files</h3>
            <form method="POST" action="{{ route('internal.media-library.store') }}" enctype="multipart/form-data">
                @csrf
                <label class="portal-label">Files</label>
                <input type="file" name="files[]" multiple class="portal-input" required>
                <p class="text-xs text-slate-500 mt-1">Images, video, PDF, Word, Excel, PowerPoint, ZIP. Up to 50MB each.</p>

                @if ($currentFolderId)
                    <input type="hidden" name="folder_id" value="{{ $currentFolderId }}">
                @endif

                <div class="flex items-center gap-3 mt-4">
                    <button type="submit" class="btn-portal-primary">Upload</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('upload-modal').classList.add('hidden')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Folder modal --}}
    <div id="folder-modal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div class="portal-card p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold text-white mb-4">New folder</h3>
            <form method="POST" action="{{ route('internal.media-library.folders.store') }}">
                @csrf
                <label class="portal-label">Name</label>
                <input type="text" name="name" class="portal-input" required>
                <div class="flex items-center gap-3 mt-4">
                    <button type="submit" class="btn-portal-primary">Create</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('folder-modal').classList.add('hidden')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection
