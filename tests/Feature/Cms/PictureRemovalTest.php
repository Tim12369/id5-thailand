<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Models\OrgChartMember;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\ArticleMapper;
use App\Services\Cms\Mappers\PersonMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A picture an editor removes in WordPress must disappear from the website.
 *
 * It used not to: with no picture in the payload the website kept showing its
 * old copy, so "delete the picture" appeared to do nothing. What is kept now
 * is only a picture that still exists in WordPress but could not be fetched.
 */
class PictureRemovalTest extends TestCase
{
    use RefreshDatabase;

    private const PHOTO = ['id' => 7, 'url' => 'https://cms.test/wp-content/uploads/2026/10/officer.jpg', 'filename' => 'officer.jpg', 'mime_type' => 'image/jpeg', 'size' => 9, 'alt' => ''];

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.enabled' => true, 'cms.base_url' => 'https://cms.test', 'cms.retries' => 0]);
        Storage::fake('public');
    }

    private function wordpressServes(int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['cms.test/wp-content/uploads/*' => Http::response('JPEG-BYTES', $status, ['Content-Type' => 'image/jpeg'])]);
    }

    private function person(?array $image): array
    {
        return [
            'wp_post_id' => 134, 'title' => 'พ.ต.อ. ทดสอบ ระบบ', 'status' => 'published', 'menu_order' => 1, 'image' => $image,
            'fields' => ['tier_label_th' => 'ผู้บังคับการ', 'tier_order' => 1],
            'terms'  => ['id5_org_section' => [[
                'term_id' => 9, 'name' => 'กองบังคับการ', 'slug' => 'division5',
                'fields'  => ['theme' => 'navy', 'sort_order' => 1, 'show_on_about_page' => true],
            ]]],
        ];
    }

    private function mapper(string $class)
    {
        return new $class(new MediaMirror(config('cms.media')));
    }

    #[Test]
    public function a_person_s_photo_removed_in_wordpress_leaves_the_website(): void
    {
        $this->wordpressServes();
        $mapper = $this->mapper(PersonMapper::class);

        $mapper->upsert($this->person(self::PHOTO));
        $photo = OrgChartMember::firstOrFail()->photo_path;
        $this->assertNotNull($photo);
        $this->get('/about')->assertOk()->assertSee('storage/'.$photo, false);

        // The editor presses "remove featured image" and updates.
        $mapper->upsert($this->person(null));

        $this->assertNull(OrgChartMember::firstOrFail()->photo_path);
        $this->get('/about')->assertOk()->assertDontSee('storage/'.$photo, false)->assertSee('พ.ต.อ. ทดสอบ ระบบ');
    }

    #[Test]
    public function a_photo_that_could_not_be_downloaded_just_now_is_kept(): void
    {
        $this->wordpressServes();
        $mapper = $this->mapper(PersonMapper::class);
        $mapper->upsert($this->person(self::PHOTO));
        $photo = OrgChartMember::firstOrFail()->photo_path;

        // WordPress still has a picture, a different one, but the download fails.
        $this->wordpressServes(500);
        $mapper->upsert($this->person(['url' => 'https://cms.test/wp-content/uploads/2026/10/new.jpg', 'filename' => 'new.jpg'] + self::PHOTO));

        $this->assertSame($photo, OrgChartMember::firstOrFail()->photo_path);
    }

    #[Test]
    public function a_news_story_s_picture_removed_in_wordpress_leaves_the_website(): void
    {
        $this->wordpressServes();
        $mapper = $this->mapper(ArticleMapper::class);

        $story = fn (?array $image) => [
            'wp_post_id' => 900, 'post_type' => 'post', 'article_type' => 'news', 'title' => 'ข่าวมีรูป', 'slug' => 'news-with-picture',
            'excerpt' => '', 'content' => '<p>เนื้อหา</p>', 'status' => 'published', 'menu_order' => 0,
            'published_at' => '2026-03-01T03:00:00+00:00', 'image' => $image, 'fields' => [], 'terms' => [],
        ];

        $mapper->upsert($story(self::PHOTO));
        $this->assertNotNull(Article::firstOrFail()->thumbnail);

        $mapper->upsert($story(null));
        $this->assertNull(Article::firstOrFail()->thumbnail);
    }
}
