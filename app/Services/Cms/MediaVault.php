<?php

namespace App\Services\Cms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps a second copy, in the database, of the files mirrored from WordPress.
 *
 * Some hosts give the application a disk that is wiped whenever the service
 * restarts or goes to sleep. The mirrored pictures live on that disk, so on
 * such a host every picture on the website would disappear until its item was
 * synced again. With CMS_MEDIA_PERSIST switched on, each mirrored file is also
 * written here, and `cms:media:restore` puts the files back when the service
 * starts.
 *
 * Switched off (the default) this class does nothing at all, and nothing is
 * written to the database.
 */
class MediaVault
{
    public function __construct(
        private readonly bool $enabled,
    ) {}

    public static function fromConfig(): self
    {
        return new self((bool) config('cms.media.persist'));
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Keep a copy of a file that has just been written to a disk.
     */
    public function keep(string $disk, string $path, string $bytes, ?string $mime = null): void
    {
        if (! $this->enabled) {
            return;
        }

        try {
            DB::table('cms_media_files')->updateOrInsert(
                ['disk' => $disk, 'path' => $path],
                [
                    'mime'       => $mime ?: null,
                    'size'       => strlen($bytes),
                    'content'    => base64_encode($bytes),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // The file is on the disk and the website works; only its
            // survival across a restart is at stake. Say so and carry on.
            Log::channel('cms')->warning('Could not keep a database copy of a mirrored file.', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Keep a copy of a file that is already on the disk but not yet here —
     * one mirrored before this was switched on.
     */
    public function keepExisting(string $disk, string $path, ?string $mime = null): void
    {
        if (! $this->enabled || $this->has($disk, $path)) {
            return;
        }

        $bytes = Storage::disk($disk)->get($path);

        if (is_string($bytes)) {
            $this->keep($disk, $path, $bytes, $mime);
        }
    }

    public function has(string $disk, string $path): bool
    {
        return $this->enabled
            && DB::table('cms_media_files')->where('disk', $disk)->where('path', $path)->exists();
    }

    /**
     * Put one file back on its disk. False when there is no copy of it.
     */
    public function restore(string $disk, string $path): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $content = DB::table('cms_media_files')->where('disk', $disk)->where('path', $path)->value('content');
        $bytes   = is_string($content) ? base64_decode($content, true) : false;

        if ($bytes === false) {
            return false;
        }

        return (bool) Storage::disk($disk)->put($path, $bytes);
    }

    /**
     * Put back every file that is missing from its disk.
     *
     * One file is read at a time, so the memory needed is that of the largest
     * file rather than of the whole library.
     *
     * @return array{restored: int, present: int, failed: int}
     */
    public function restoreAll(): array
    {
        $count = ['restored' => 0, 'present' => 0, 'failed' => 0];

        if (! $this->enabled) {
            return $count;
        }

        foreach (DB::table('cms_media_files')->orderBy('id')->get(['disk', 'path']) as $file) {
            if (Storage::disk($file->disk)->exists($file->path)) {
                $count['present']++;

                continue;
            }

            $count[$this->restore($file->disk, $file->path) ? 'restored' : 'failed']++;
        }

        return $count;
    }
}
