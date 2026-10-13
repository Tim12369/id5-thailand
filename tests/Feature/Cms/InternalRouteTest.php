<?php

namespace Tests\Feature\Cms;

use App\Services\Cms\InternalRoute;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\WordPressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Where WordPress runs in the same container as the website, requests meant
 * for it go to the local web server instead of out to the public address —
 * which, while a new version is starting, still belongs to the old one.
 * Only the destination changes; every address that is stored or shown stays
 * the public one.
 */
class InternalRouteTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC = 'https://www.example.go.th/wp';

    private const LOCAL = 'http://127.0.0.1:10000/wp';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled' => true, 'cms.base_url' => self::PUBLIC, 'cms.retries' => 0,
            'cms.auth.username' => 'id5-sync', 'cms.auth.password' => 'app-password',
        ]);
    }

    #[Test]
    public function without_an_internal_address_nothing_changes(): void
    {
        $this->assertSame(self::PUBLIC.'/wp-json/id5/v1/health', InternalRoute::url(self::PUBLIC.'/wp-json/id5/v1/health'));
        $this->assertSame([], InternalRoute::headers(self::PUBLIC.'/wp-json/id5/v1/health'));
    }

    #[Test]
    public function requests_to_wordpress_are_sent_to_the_local_web_server(): void
    {
        config(['cms.internal_url' => self::LOCAL.'/']);
        Http::fake(['127.0.0.1:10000/*' => Http::response(['post_type' => 'post', 'items' => []])]);

        (new WordPressClient(config('cms')))->collection('post');

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), self::LOCAL.'/wp-json/id5/v1/collection/post')
                // WordPress only accepts an application password over HTTPS;
                // locally nothing but this header says the visit was secure.
                && $request->hasHeader('X-Forwarded-Proto', 'https')
                && $request->hasHeader('Authorization');
        });
    }

    #[Test]
    public function pictures_are_fetched_locally_but_recorded_under_their_public_address(): void
    {
        config(['cms.internal_url' => self::LOCAL]);
        Storage::fake('public');
        Http::fake(['127.0.0.1:10000/*' => Http::response('PICTURE', 200, ['Content-Type' => 'image/jpeg'])]);

        $mirror = new MediaMirror(config('cms.media'));
        $first  = $mirror->mirror(['id' => 7, 'url' => self::PUBLIC.'/wp-content/uploads/2026/10/a.jpg', 'filename' => 'a.jpg', 'mime_type' => 'image/jpeg']);

        Http::assertSent(fn (Request $request) => $request->url() === self::LOCAL.'/wp-content/uploads/2026/10/a.jpg');
        Storage::disk('public')->assertExists($first);

        // The file is named from the public address, so it is the same file
        // whichever way it was fetched.
        config(['cms.internal_url' => '']);
        $this->assertSame($first, $mirror->mirror(['id' => 7, 'url' => self::PUBLIC.'/wp-content/uploads/2026/10/a.jpg', 'filename' => 'a.jpg', 'mime_type' => 'image/jpeg']));
    }

    #[Test]
    public function an_address_that_is_not_wordpress_s_is_left_alone(): void
    {
        config(['cms.internal_url' => self::LOCAL]);

        $this->assertSame('https://elsewhere.example/photo.jpg', InternalRoute::url('https://elsewhere.example/photo.jpg'));
        $this->assertSame([], InternalRoute::headers('https://elsewhere.example/photo.jpg'));
    }
}
