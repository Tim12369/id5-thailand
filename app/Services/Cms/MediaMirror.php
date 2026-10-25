<?php

namespace App\Services\Cms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Copies images and documents out of the WordPress Media Library and into this
 * application's own storage.
 *
 * The public site therefore serves its own assets: no visitor request ever
 * reaches WordPress, nothing breaks if the editing install moves, and the
 * existing `asset('storage/'.$path)` accessors on the models keep working
 * unchanged.
 *
 * Files are named from a hash of their source URL, so re-syncing the same
 * content does not re-download or duplicate anything.
 */
class MediaMirror
{
    private readonly MediaVault $vault;

    public function __construct(
        private readonly array $config,
        ?MediaVault $vault = null,
    ) {
        // Database copies for hosts whose disk is wiped on restart; inert
        // unless `persist` is switched on.
        $this->vault = $vault ?? new MediaVault((bool) ($config['persist'] ?? false));
    }

    public static function fromConfig(): self
    {
        return new self(config('cms.media'));
    }

    /**
     * Mirror a WordPress attachment onto the public disk.
     *
     * No attachment means the editor removed the picture in WordPress, so it
     * is removed here too (null). That is different from a picture that
     * exists but could not be downloaded just now — a network hiccup, an
     * oversized file — where the one already on the website is kept.
     *
     * @param  array|null $attachment The serialized attachment from WordPress.
     * @param  string|null $current   The path currently stored on the model.
     * @return string|null            A path relative to the public disk, or null.
     */
    public function mirror(?array $attachment, ?string $current = null): ?string
    {
        if (empty($attachment['url'])) {
            return null;
        }

        return $this->store($attachment, $this->config['disk'], $this->config['directory'], $current);
    }

    /**
     * Mirror onto the private disk. Used for downloadable forms, which must stay
     * behind the throttled Laravel download route rather than become public
     * files.
     */
    public function mirrorPrivate(?array $attachment, ?string $current = null): ?string
    {
        return $this->store(
            $attachment,
            $this->config['private_disk'],
            $this->config['private_directory'],
            $current
        );
    }

    /** What an editor may place inside a piece of text: extension => type. */
    private const IN_TEXT = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'pdf' => 'application/pdf',
    ];

    /**
     * Mirror the pictures (and linked documents) an editor placed inside a
     * piece of text with WordPress's "add media" button.
     *
     * Each one that lives in the WordPress Media Library is copied here and
     * the text is pointed at the copy, so the article shows its pictures
     * without a visitor's browser ever contacting WordPress. Anything that is
     * not a Media Library file — a picture on another website, an ordinary
     * link — is left exactly as written, and so is a file that could not be
     * downloaded just now: it is picked up at the next sync.
     *
     * @param  string|null   $html  Finished HTML from WordPress.
     * @param  array<string> $bases Addresses the Media Library is served from.
     */
    public function mirrorHtml(?string $html, array $bases): ?string
    {
        $bases = array_values(array_unique(array_filter(array_map(
            fn ($base) => is_string($base) && preg_match('#^https?://#i', $base) ? rtrim($base, '/').'/' : null,
            $bases
        ))));

        if (blank($html) || ! $bases || ! preg_match('/<(?:img|a|object)\b/i', $html)) {
            return $html;
        }

        // A picture's src, a link's href, and the data of a PDF shown inline
        // (WordPress's File block embeds one with <object data="…">).
        return preg_replace_callback('/<(img|a|object)\b[^>]*>/i', function (array $tag) use ($bases) {
            $attribute = ['img' => 'src', 'a' => 'href', 'object' => 'data'][strtolower($tag[1])];

            if (! preg_match('/\s'.$attribute.'\s*=\s*(["\'])(.*?)\1/is', $tag[0], $found, PREG_OFFSET_CAPTURE)) {
                return $tag[0];
            }

            $url   = html_entity_decode(trim($found[2][0]), ENT_QUOTES | ENT_HTML5);
            $local = $this->localCopy($url, $bases);

            if ($local === null) {
                return $tag[0];
            }

            $html = substr_replace($tag[0], e($local), $found[2][1], strlen($found[2][0]));

            // The other sizes WordPress lists would still be fetched from it.
            return $attribute === 'src'
                ? preg_replace('/\s(?:srcset|sizes)\s*=\s*(["\']).*?\1/is', '', $html)
                : $html;
        }, $html) ?? $html;
    }

    /**
     * The address on this website of a Media Library file, once copied.
     */
    private function localCopy(string $url, array $bases): ?string
    {
        $inLibrary = false;

        foreach ($bases as $base) {
            $inLibrary = $inLibrary || stripos($url, $base) === 0;
        }

        $path      = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! $inLibrary || ! isset(self::IN_TEXT[$extension]) || str_contains($path, '..')) {
            return null;
        }

        $stored = $this->store(
            ['url' => $url, 'filename' => basename($path), 'mime_type' => self::IN_TEXT[$extension]],
            $this->config['disk'],
            $this->config['directory'],
            null
        );

        if ($stored === null) {
            return null;
        }

        // Root-relative, so it does not matter which address the site is
        // being served from when the text is shown.
        return parse_url(Storage::disk($this->config['disk'])->url($stored), PHP_URL_PATH) ?: null;
    }

    /**
     * The disk a privately mirrored file lives on, for callers that record it.
     */
    public function privateDisk(): string
    {
        return $this->config['private_disk'];
    }

    private function store(?array $attachment, string $disk, string $directory, ?string $current): ?string
    {
        $url = $attachment['url'] ?? null;

        if (! $url) {
            // Only the private mirror gets here without a file (see mirror()).
            // A download form must always have its PDF, so the one already on
            // the website stays until a new file is chosen.
            return $current;
        }

        $mime = $attachment['mime_type'] ?? '';

        if ($mime && ! in_array($mime, $this->config['allowed_mime'], true)) {
            Log::channel('cms')->warning('Refused to mirror an unsupported file type.', [
                'url'  => $url,
                'mime' => $mime,
            ]);

            return $current;
        }

        $target = $this->pathFor($directory, $url, $attachment['filename'] ?? null);

        // Already here — or it was, before the host wiped its disk, in which
        // case the database copy is put back instead of downloading again.
        if (Storage::disk($disk)->exists($target) || $this->vault->restore($disk, $target)) {
            $this->vault->keepExisting($disk, $target, $mime ?: null);
            $this->remember($disk, $target, $attachment['id'] ?? null);

            return $target;
        }

        try {
            $response = Http::timeout(config('cms.timeout'))
                ->connectTimeout(config('cms.connect_timeout'))
                ->withOptions(['verify' => config('cms.verify_tls')])
                ->withUserAgent('ID5-Laravel-CMS-Sync')
                ->withHeaders(InternalRoute::headers($url))
                ->get(InternalRoute::url($url));

            if ($response->failed()) {
                Log::channel('cms')->warning('Could not download media from WordPress.', [
                    'url'    => $url,
                    'status' => $response->status(),
                ]);

                return $current;
            }

            $body = $response->body();

            if (strlen($body) > $this->config['max_bytes']) {
                Log::channel('cms')->warning('Refused to mirror an oversized file.', [
                    'url'   => $url,
                    'bytes' => strlen($body),
                ]);

                return $current;
            }

            Storage::disk($disk)->put($target, $body);
            $this->vault->keep($disk, $target, $body, $mime ?: null);

            $this->remember($disk, $target, $attachment['id'] ?? null);

            return $target;
        } catch (\Throwable $e) {
            Log::channel('cms')->warning('Media mirroring failed.', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return $current;
        }
    }

    /**
     * Record that a local file is a copy of a given WordPress attachment.
     *
     * The content export looks this up before uploading anything, so a file
     * that came *from* WordPress is never uploaded back *to* it as a second
     * copy if the export is run again later.
     */
    private function remember(string $disk, string $path, mixed $attachmentId): void
    {
        if (! $attachmentId) {
            return;
        }

        DB::table('cms_sync_state')->updateOrInsert(
            ['kind' => 'media', 'scope' => $disk.':'.$path],
            ['reference' => (string) $attachmentId, 'status' => 'ok', 'last_run_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * A stable, collision-free path derived from the source URL. Keeping the
     * original extension means the file is still served with the right type.
     */
    private function pathFor(string $directory, string $url, ?string $filename): string
    {
        $extension = pathinfo($filename ?: parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', (string) $extension) ?: 'bin';

        return sprintf('%s/%s.%s', $directory, substr(hash('sha256', $url), 0, 32), strtolower($extension));
    }
}
