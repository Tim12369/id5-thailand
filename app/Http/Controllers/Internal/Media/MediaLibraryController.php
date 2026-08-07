<?php

namespace App\Http\Controllers\Internal\Media;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/quicktime',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip', 'application/x-zip-compressed',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('media.view'), 403);

        $query = Media::with(['folder', 'tags', 'uploader'])->latest();

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->integer('folder_id'));
        }

        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                match ($request->string('category')->toString()) {
                    Media::CATEGORY_IMAGE => $q->where('mime_type', 'like', 'image/%'),
                    Media::CATEGORY_VIDEO => $q->where('mime_type', 'like', 'video/%'),
                    Media::CATEGORY_DOCUMENT => $q->whereIn('mime_type', array_diff(self::ALLOWED_MIMES, [
                        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
                        'video/mp4', 'video/webm', 'video/quicktime',
                    ])),
                    default => null,
                };
            });
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('media_tags.id', $request->integer('tag_id')));
        }

        if ($request->filled('search')) {
            $query->where('original_name', 'like', '%' . $request->string('search') . '%');
        }

        return view('internal.media.index', [
            'media' => $query->paginate(24)->withQueryString(),
            'folders' => MediaFolder::whereNull('parent_id')->with('children')->orderBy('name')->get(),
            'tags' => MediaTag::orderBy('name')->get(),
            'currentFolderId' => $request->integer('folder_id') ?: null,
            'currentCategory' => $request->string('category')->toString(),
            'currentTagId' => $request->integer('tag_id') ?: null,
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('media.manage'), 403);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:51200', 'mimetypes:' . implode(',', self::ALLOWED_MIMES)],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('media/' . date('Y/m'), 'public');

            [$width, $height] = str_starts_with($file->getMimeType(), 'image/')
                ? (@getimagesize($file->getRealPath()) ?: [null, null])
                : [null, null];

            Media::create([
                'folder_id' => $request->integer('folder_id') ?: null,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Upload complete.');
    }

    public function update(Request $request, Media $medium): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('media.manage'), 403);

        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            'tag_names' => ['nullable', 'string', 'max:500'],
        ]);

        $medium->update([
            'alt_text' => $data['alt_text'] ?? null,
            'folder_id' => $data['folder_id'] ?? null,
        ]);

        if (isset($data['tag_names'])) {
            $tagIds = collect(explode(',', $data['tag_names']))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->map(fn ($name) => MediaTag::firstOrCreate(['name' => $name])->id);

            $medium->tags()->sync($tagIds);
        }

        return back()->with('success', 'Media updated.');
    }

    public function destroy(Request $request, Media $medium): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('media.manage'), 403);

        Storage::disk($medium->disk)->delete($medium->path);
        $medium->delete();

        return back()->with('success', 'Media deleted.');
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('media.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        MediaFolder::create($data);

        return back()->with('success', 'Folder created.');
    }

    public function destroyFolder(Request $request, MediaFolder $folder): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('media.manage'), 403);

        if ($folder->media()->exists() || $folder->children()->exists()) {
            return back()->withErrors(['folder' => 'Cannot delete a folder that still contains files or subfolders.']);
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted.');
    }
}
