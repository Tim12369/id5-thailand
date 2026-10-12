<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\ArticleMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pictures on a host whose disk is wiped at every restart.
 *
 * With CMS_MEDIA_PERSIST on, each file mirrored from WordPress is also kept in
 * the database, and the start-up command puts the files back — so a restart
 * never leaves the website without its pictures, and never needs WordPress to
 * be reachable to recover them.
 */
class MediaVaultTest extends TestCase
{
    use RefreshDatabase;

    private const UPLOADS = 'https://cms.test/wp-content/uploads';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.enabled' => true, 'cms.base_url' => 'https://cms.test', 'cms.retries' => 0]);
        Storage::fake('public');
        $this->wordpressServes();
    }

    private function wordpressServes(int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['cms.test/*' => Http::response('PICTURE-BYTES', $status, ['Content-Type' => 'image/jpeg'])]);
    }

    private function sync(): Article
    {
        (new ArticleMapper(new MediaMirror(config('cms.media'))))->upsert([
            'wp_post_id' => 900, 'post_type' => 'post', 'article_type' => 'news', 'title' => 'ข่าวมีรูป', 'slug' => 'with-pictures',
            'excerpt' => '', 'status' => 'published', 'menu_order' => 0, 'published_at' => '2026-03-01T03:00:00+00:00',
            'content' => '<p><img src="'.self::UPLOADS.'/2026/10/in-text.jpg" alt="" /></p>',
            'image' => ['id' => 7, 'url' => self::UPLOADS.'/2026/10/cover.jpg', 'filename' => 'cover.jpg', 'mime_type' => 'image/jpeg'],
            'uploads_url' => self::UPLOADS, 'fields' => [], 'terms' => [],
        ]);

        return Article::firstOrFail();
    }

    /** What a restart does on such a host. */
    private function wipeTheDisk(): void
    {
        Storage::disk('public')->deleteDirectory('cms');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function by_default_nothing_is_written_to_the_database(): void
    {
        $this->sync();

        $this->assertCount(2, Storage::disk('public')->allFiles('cms'));
        $this->assertSame(0, DB::table('cms_media_files')->count());

        $this->artisan('cms:media:restore')->assertSuccessful();
    }

    #[Test]
    public function with_persistence_on_every_mirrored_file_is_kept_and_survives_a_restart(): void
    {
        config(['cms.media.persist' => true]);

        $article = $this->sync();
        $files   = Storage::disk('public')->allFiles('cms');

        $this->assertCount(2, $files, 'the cover picture and the picture in the text');
        $this->assertSame(2, DB::table('cms_media_files')->count());
        $this->assertSame(strlen('PICTURE-BYTES'), (int) DB::table('cms_media_files')->where('path', $article->thumbnail)->value('size'));

        $this->wipeTheDisk();
        $this->wordpressServes(500);                 // and WordPress happens to be asleep

        $this->artisan('cms:media:restore')->assertSuccessful();

        $this->assertEqualsCanonicalizing($files, Storage::disk('public')->allFiles('cms'));
        $this->assertSame('PICTURE-BYTES', Storage::disk('public')->get($article->thumbnail));
        $this->get('/news/with-pictures')->assertOk()->assertSee('storage/'.$article->thumbnail, false);
    }

    #[Test]
    public function a_sync_after_a_restart_takes_the_file_from_the_database_instead_of_downloading_it_again(): void
    {
        config(['cms.media.persist' => true]);

        $before = $this->sync();
        $this->wipeTheDisk();
        $this->wordpressServes(500);

        $after = $this->sync();

        $this->assertSame($before->thumbnail, $after->thumbnail);
        $this->assertSame($before->body_th, $after->body_th);
        $this->assertCount(2, Storage::disk('public')->allFiles('cms'));
    }

    #[Test]
    public function a_file_mirrored_before_persistence_was_switched_on_is_picked_up_at_the_next_sync(): void
    {
        $this->sync();
        $this->assertSame(0, DB::table('cms_media_files')->count());

        config(['cms.media.persist' => true]);
        $this->sync();

        $this->assertSame(2, DB::table('cms_media_files')->count());
    }

    #[Test]
    public function restoring_leaves_files_that_are_already_in_place_alone(): void
    {
        config(['cms.media.persist' => true]);
        $article = $this->sync();

        Storage::disk('public')->put($article->thumbnail, 'CHANGED-ON-DISK');

        $this->artisan('cms:media:restore')->assertSuccessful();

        $this->assertSame('CHANGED-ON-DISK', Storage::disk('public')->get($article->thumbnail));
    }
}
