<?php

namespace App\Services\Cms;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The only place this application talks to WordPress over HTTP.
 *
 * Every call is read-only except the ones the one-time content export uses, and
 * all of them run server-side with an Application Password that never leaves
 * the backend. Nothing here is ever reached while rendering a visitor's page —
 * see App\Services\Cms\CmsSyncService for how content gets into the database.
 */
class WordPressClient
{
    public function __construct(
        private readonly array $config,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('cms'));
    }

    public function isConfigured(): bool
    {
        return $this->config['base_url'] !== ''
            && filled($this->config['auth']['username'])
            && filled($this->config['auth']['password']);
    }

    /**
     * Fail fast with a message that names the missing setting rather than
     * letting a half-configured install produce a confusing 401.
     */
    private function assertConfigured(): void
    {
        if ($this->config['base_url'] === '') {
            throw new RuntimeException('CMS_WORDPRESS_URL is not set.');
        }

        if (blank($this->config['auth']['username']) || blank($this->config['auth']['password'])) {
            throw new RuntimeException('CMS_WORDPRESS_USER / CMS_WORDPRESS_APP_PASSWORD are not set.');
        }
    }

    // ─── Reads (id5/v1) ───────────────────────────────────────────────────

    /** Whole-site payload: all content, terms, menus and settings. */
    public function site(array $types = []): array
    {
        $query = $types ? ['types' => implode(',', $types)] : [];

        return $this->getJson('/wp-json/id5/v1/site', $query);
    }

    /** One content type's posts. */
    public function collection(string $postType): array
    {
        return $this->getJson("/wp-json/id5/v1/collection/{$postType}");
    }

    /** One post by WordPress ID. Returns null when it no longer exists. */
    public function post(int $wpPostId): ?array
    {
        $response = $this->request()->get($this->url("/wp-json/id5/v1/post/{$wpPostId}"));

        if ($response->status() === 404) {
            return null;
        }

        return $this->decode($response, "/wp-json/id5/v1/post/{$wpPostId}");
    }

    public function settings(): array
    {
        return $this->getJson('/wp-json/id5/v1/settings');
    }

    public function menus(): array
    {
        return $this->getJson('/wp-json/id5/v1/menus');
    }

    public function terms(): array
    {
        return $this->getJson('/wp-json/id5/v1/terms');
    }

    /**
     * The wording editors have changed: ['texts' => [[key, locale, value]],
     * 'initialised' => bool].
     */
    public function texts(): array
    {
        return $this->getJson('/wp-json/id5/v1/texts');
    }

    /**
     * Liveness probe. Never throws: callers use it to decide whether to bother.
     */
    public function health(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            return $this->request()
                ->timeout(5)
                ->get($this->url('/wp-json/id5/v1/health'))
                ->successful();
        } catch (\Throwable $e) {
            Log::channel('cms')->warning('WordPress health check failed.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    // ─── Writes, used only by the one-time content export ─────────────────

    /**
     * Create or update one content item in WordPress.
     *
     * The plugin's own `id5/v1/import` endpoint owns the mapping, so this side
     * never has to reason about meta key names, protected-meta rules or term
     * creation. The call is idempotent: the plugin matches on
     * `legacy_type` + `legacy_id` and updates an existing post rather than
     * creating a second one.
     */
    public function importItem(array $payload): array
    {
        return $this->send('post', '/wp-json/id5/v1/import', $payload);
    }

    /** Push the site-wide Customizer values. */
    public function importSettings(array $settings): array
    {
        return $this->send('post', '/wp-json/id5/v1/import/settings', ['settings' => $settings]);
    }

    /**
     * @param  array<string,array<int,array{name:string,slug:string,fields:array}>> $terms By taxonomy.
     */
    public function importTerms(array $terms): array
    {
        return $this->send('post', '/wp-json/id5/v1/import/terms', ['terms' => $terms]);
    }

    /**
     * @param  array<int,array{key:string,locale:string,value:string}> $texts
     */
    public function importTexts(array $texts): array
    {
        return $this->send('post', '/wp-json/id5/v1/import/texts', ['texts' => $texts]);
    }

    /**
     * Upload a local file into the WordPress Media Library and return the
     * created attachment. Callers are responsible for not uploading the same
     * file twice — App\Services\Cms\CmsPushService keeps that record locally.
     */
    public function uploadMedia(string $absolutePath, string $filename, string $mimeType): array
    {
        $this->assertConfigured();

        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException("Could not read {$absolutePath} for upload.");
        }

        $response = $this->request()
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ])
            ->withBody($contents, $mimeType)
            ->post($this->url('/wp-json/wp/v2/media'));

        return $this->decode($response, '/wp-json/wp/v2/media');
    }

    // ─── Plumbing ─────────────────────────────────────────────────────────

    private function url(string $path): string
    {
        return InternalRoute::url($this->config['base_url'].$path);
    }

    private function getJson(string $path, array $query = []): array
    {
        $this->assertConfigured();

        $response = $this->request()->get($this->url($path), $query);

        return $this->decode($response, $path);
    }

    private function send(string $method, string $path, array $payload): array
    {
        $this->assertConfigured();

        $response = $this->request()->{$method}($this->url($path), $payload);

        return $this->decode($response, $path);
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth(
            (string) $this->config['auth']['username'],
            (string) $this->config['auth']['password'],
        )
            ->withHeaders(['Accept' => 'application/json'] + InternalRoute::headers($this->config['base_url']))
            ->withUserAgent('ID5-Laravel-CMS-Sync')
            ->withOptions(['verify' => $this->config['verify_tls']])
            ->connectTimeout($this->config['connect_timeout'])
            ->timeout($this->config['timeout'])
            ->retry(
                $this->config['retries'] + 1,
                $this->config['retry_delay_ms'],
                throw: false,
            );
    }

    /**
     * Turn a response into an array, or raise a message that is safe to log but
     * never contains the credential used to make the call.
     */
    private function decode(Response $response, string $path): array
    {
        if ($response->failed()) {
            Log::channel('cms')->error('WordPress request failed.', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException(
                "WordPress request to {$path} failed with HTTP {$response->status()}."
            );
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new RuntimeException("WordPress returned an unexpected payload for {$path}.");
        }

        return $decoded;
    }
}
