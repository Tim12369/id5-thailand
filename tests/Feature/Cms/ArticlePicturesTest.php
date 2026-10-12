<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\ArticleMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pictures in news, announcements and alerts.
 *
 * An editor has two ways to add one in WordPress: the cover picture ("featured
 * image") and the "add media" button that places pictures inside the text.
 * Both must reach the public website, and both must be served by the website
 * itself — a visitor's browser never has to contact WordPress.
 */
class ArticlePicturesTest extends TestCase
{
    use RefreshDatabase;

    private const UPLOADS = 'https://cms.test/wp-content/uploads';

    private const COVER = ['id' => 7, 'url' => self::UPLOADS.'/2026/10/cover.jpg', 'filename' => 'cover.jpg', 'mime_type' => 'image/jpeg', 'size' => 9, 'alt' => ''];

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
        Http::fake([
            'cms.test/wp-content/uploads/*' => Http::response('FILE-BYTES', $status, ['Content-Type' => 'image/jpeg']),
            '*'                             => Http::response('SHOULD-NEVER-BE-FETCHED', 200),
        ]);
    }

    private function payload(string $type, array $with = []): array
    {
        return $with + [
            'wp_post_id' => 900, 'post_type' => ['news' => 'post', 'announcement' => 'id5_announcement', 'alert' => 'id5_alert'][$type],
            'article_type' => $type, 'title' => 'เรื่องทดสอบรูปภาพ', 'slug' => 'picture-test', 'excerpt' => '',
            'content' => '<p>เนื้อหา</p>', 'status' => 'published', 'menu_order' => 0,
            'published_at' => '2026-03-01T03:00:00+00:00', 'image' => null, 'uploads_url' => self::UPLOADS,
            'fields' => [], 'terms' => [],
        ];
    }

    private function sync(array $payload): Article
    {
        (new ArticleMapper(new MediaMirror(config('cms.media'))))->upsert($payload);

        return Article::where('wp_post_id', $payload['wp_post_id'])->firstOrFail();
    }

    /** The local addresses of every picture in a piece of HTML. */
    private function sources(string $html): array
    {
        preg_match_all('/<img[^>]*\ssrc="([^"]*)"/', $html, $found);

        return $found[1];
    }

    // ─── Pictures placed inside the text ──────────────────────────────────

    #[Test]
    public function a_picture_placed_in_the_text_is_copied_to_the_website(): void
    {
        $article = $this->sync($this->payload('news', ['content' =>
            '<p>ก่อนรูป</p>'
            .'<p><a href="'.self::UPLOADS.'/2026/10/office.jpg"><img class="alignleft size-medium wp-image-12" src="'.self::UPLOADS.'/2026/10/office-300x200.jpg" alt="สำนักงาน" width="300" height="200" srcset="'.self::UPLOADS.'/2026/10/office-300x200.jpg 300w, '.self::UPLOADS.'/2026/10/office.jpg 1200w" sizes="(max-width: 300px) 100vw, 300px" /></a></p>'
            .'<p>หลังรูป</p>',
        ]));

        $this->assertStringNotContainsString('cms.test', $article->body_th, 'nothing in the text still points at WordPress');

        [$src] = $this->sources($article->body_th);
        $this->assertMatchesRegularExpression('#^/storage/cms/[0-9a-f]{32}\.jpg$#', $src);
        Storage::disk('public')->assertExists(substr($src, strlen('/storage/')));

        // What the editor chose is kept: the position, the size, the description.
        $this->assertStringContainsString('class="alignleft size-medium wp-image-12"', $article->body_th);
        $this->assertStringContainsString('alt="สำนักงาน" width="300" height="200"', $article->body_th);
        $this->assertStringNotContainsString('srcset', $article->body_th);
        $this->assertStringContainsString('<p>ก่อนรูป</p>', $article->body_th);
        $this->assertStringContainsString('<p>หลังรูป</p>', $article->body_th);

        // The full-size picture it links to is a copy as well.
        $this->assertMatchesRegularExpression('#<a href="/storage/cms/[0-9a-f]{32}\.jpg">#', $article->body_th);

        // And the public page shows it.
        $this->get('/news/picture-test')->assertOk()->assertSee('src="'.$src.'"', false);
    }

    #[Test]
    public function pictures_in_the_english_text_are_copied_too(): void
    {
        $article = $this->sync($this->payload('announcement', ['fields' => [
            'body_en' => '<p>English</p><p><img src="'.self::UPLOADS.'/2026/10/notice.png" alt="" /></p>',
        ]]));

        $this->assertMatchesRegularExpression('#<img src="/storage/cms/[0-9a-f]{32}\.png" alt="" />#', $article->body_en);
    }

    #[Test]
    public function saving_again_does_not_download_or_duplicate_the_picture(): void
    {
        $payload = $this->payload('news', ['content' => '<p><img src="'.self::UPLOADS.'/2026/10/office.jpg" /></p>']);

        $first = $this->sync($payload)->body_th;
        $this->wordpressServes(500);                      // WordPress is now unreachable
        $second = $this->sync($payload)->body_th;

        $this->assertSame($first, $second);
        $this->assertCount(1, Storage::disk('public')->allFiles('cms'));
    }

    #[Test]
    public function a_picture_from_another_website_and_ordinary_links_are_left_exactly_as_written(): void
    {
        $content = '<p><img src="https://elsewhere.example/photo.jpg" alt="" /> <a href="https://www.immigration.go.th/">เว็บไซต์</a> '
            .'<a href="https://cms.test/wp-content/uploads/2026/10/page.php">ไฟล์</a> <a href="/contact">ติดต่อ</a></p>';

        $article = $this->sync($this->payload('news', ['content' => $content]));

        $this->assertSame($content, $article->body_th);
        $this->assertSame([], Storage::disk('public')->allFiles('cms'));
        Http::assertNothingSent();
    }

    #[Test]
    public function a_picture_that_cannot_be_downloaded_just_now_keeps_working_from_wordpress(): void
    {
        $this->wordpressServes(500);
        $content = '<p><img src="'.self::UPLOADS.'/2026/10/office.jpg" alt="" /></p>';

        $this->assertSame($content, $this->sync($this->payload('news', ['content' => $content]))->body_th);

        // The next sync, with WordPress reachable again, brings it over.
        $this->wordpressServes();
        $this->assertStringNotContainsString('cms.test', $this->sync($this->payload('news', ['content' => $content]))->body_th);
    }

    #[Test]
    public function a_document_linked_in_the_text_is_copied_as_well(): void
    {
        $article = $this->sync($this->payload('announcement', [
            'content' => '<p><a href="'.self::UPLOADS.'/2026/10/order.pdf">คำสั่ง (PDF)</a></p>',
        ]));

        $this->assertMatchesRegularExpression('#<a href="/storage/cms/[0-9a-f]{32}\.pdf">คำสั่ง \(PDF\)</a>#', $article->body_th);
    }

    // ─── Where the pictures are shown ─────────────────────────────────────

    #[Test]
    public function an_announcement_shows_its_cover_picture_on_its_page_and_in_the_list(): void
    {
        $article = $this->sync($this->payload('announcement', ['image' => self::COVER]));
        $cover   = 'storage/'.$article->thumbnail;

        $this->assertNotNull($article->thumbnail);
        $this->get('/announcements/picture-test')->assertOk()->assertSee($cover, false);
        $this->get('/announcements')->assertOk()->assertSee($cover, false);
        $this->get('/news?tab=announcements')->assertOk()->assertSee($cover, false);
        $this->get('/')->assertOk()->assertSee($cover, false);
    }

    #[Test]
    public function an_announcement_without_a_picture_looks_as_it_always_did(): void
    {
        $this->sync($this->payload('announcement'));

        $this->get('/announcements/picture-test')->assertOk()->assertDontSee('<img src="'.url('storage'), false);
        $this->get('/announcements')->assertOk()->assertDontSee('storage/cms', false);
    }

    #[Test]
    public function an_alert_shows_its_cover_picture_and_the_pictures_in_its_text(): void
    {
        $article = $this->sync($this->payload('alert', [
            'excerpt' => 'ระวังผู้แอบอ้างเป็นเจ้าหน้าที่',
            'content' => '<p>รายละเอียดเพิ่มเติมของการแจ้งเตือน</p><p><img src="'.self::UPLOADS.'/2026/10/poster.jpg" alt="โปสเตอร์" /></p>',
            'image'   => self::COVER,
            'fields'  => ['alert_level' => 'warning'],
        ]));

        [$inText] = $this->sources($article->body_th);

        $this->get('/alerts')->assertOk()
            ->assertSee('ระวังผู้แอบอ้างเป็นเจ้าหน้าที่')
            ->assertSee('storage/'.$article->thumbnail, false)
            ->assertSee('รายละเอียดเพิ่มเติมของการแจ้งเตือน')
            ->assertSee('src="'.$inText.'"', false);
    }

    #[Test]
    public function an_alert_whose_text_only_repeats_its_summary_is_not_printed_twice(): void
    {
        $this->sync($this->payload('alert', ['excerpt' => 'ปิดทำการชั่วคราว', 'content' => '<p>ปิดทำการชั่วคราว</p>']));

        $html = $this->get('/alerts')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'ปิดทำการชั่วคราว'));
    }

    #[Test]
    public function news_shows_its_cover_picture_in_the_list_and_on_the_story(): void
    {
        $article = $this->sync($this->payload('news', ['image' => self::COVER]));
        $cover   = 'storage/'.$article->thumbnail;

        $this->get('/news')->assertOk()->assertSee($cover, false);
        $this->get('/news/picture-test')->assertOk()->assertSee($cover, false);
        $this->get('/')->assertOk()->assertSee($cover, false);
    }
}
