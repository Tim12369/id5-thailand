<?php

namespace Tests\Feature\Cms;

use App\Models\PageSection;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\SectionMapper;
use App\Support\PageSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Content editors build themselves in WordPress's block editor — rows of
 * columns with text, pictures and PDF documents — placed on the website's own
 * pages, or making pages of their own.
 */
class PageSectionsTest extends TestCase
{
    use RefreshDatabase;

    private const UPLOADS = 'https://cms.test/wp-content/uploads';

    private const SECRET = 'edit-mode-test-secret';

    /** What WordPress renders from a row of two columns: a picture, then text and a PDF. */
    private const BLOCKS = '<div class="wp-block-columns is-layout-flex"><div class="wp-block-column is-layout-flow">'
        .'<figure class="wp-block-image size-large"><img src="'.self::UPLOADS.'/2026/10/photo.jpg" alt="ภาพ" /></figure></div>'
        .'<div class="wp-block-column is-layout-flow"><p>ข้อความในคอลัมน์ที่สอง</p>'
        .'<div class="wp-block-file"><object class="wp-block-file__embed" data="'.self::UPLOADS.'/2026/10/form.pdf" type="application/pdf" style="width:100%;height:600px"></object>'
        .'<a href="'.self::UPLOADS.'/2026/10/form.pdf">แบบฟอร์ม</a><a href="'.self::UPLOADS.'/2026/10/form.pdf" class="wp-block-file__button" download>ดาวน์โหลด</a></div>'
        .'</div></div>';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.enabled' => true, 'cms.base_url' => 'https://cms.test', 'cms.retries' => 0, 'cms.webhook.secret' => self::SECRET]);
        Storage::fake('public');
        Http::fake(['cms.test/wp-content/uploads/*' => Http::response('FILE', 200)]);
        PageSections::reset();

        // A Thai-speaking visitor, unless a test says otherwise.
        $this->withSession(['locale' => 'th']);
    }

    private function sync(array $over = []): PageSection
    {
        $payload = array_replace_recursive([
            'wp_post_id' => 500, 'post_type' => 'id5_section', 'title' => 'ส่วนทดสอบ', 'slug' => 'section-test',
            'excerpt' => '', 'content' => self::BLOCKS, 'status' => 'published', 'menu_order' => 1,
            'uploads_url' => self::UPLOADS,
            'fields' => ['page' => '/', 'position' => 'after_hero', 'language' => 'both', 'background' => 'light'],
        ], $over);

        (new SectionMapper(new MediaMirror(config('cms.media'))))->upsert($payload);

        return PageSection::where('wp_post_id', $payload['wp_post_id'])->firstOrFail();
    }

    private function token(): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['n' => 'สมชาย', 'e' => time() + 3600])), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', 'id5-edit-mode|'.$payload, self::SECRET);
    }

    #[Test]
    public function a_section_appears_where_it_was_placed_with_its_pictures_and_pdf_copied_here(): void
    {
        $section = $this->sync();

        $this->assertStringNotContainsString('cms.test', $section->body_html, 'nothing still points at WordPress');
        $this->assertMatchesRegularExpression('#<img src="/storage/cms/[0-9a-f]{32}\.jpg"#', $section->body_html);
        $this->assertMatchesRegularExpression('#<object class="wp-block-file__embed" data="/storage/cms/[0-9a-f]{32}\.pdf"#', $section->body_html);
        $this->assertMatchesRegularExpression('#<a href="/storage/cms/[0-9a-f]{32}\.pdf" class="wp-block-file__button" download>#', $section->body_html);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="cms-section cms-section--light"', $html);
        $this->assertStringContainsString('ข้อความในคอลัมน์ที่สอง', $html);

        // Right under the banner: before the six service cards.
        $this->assertLessThan(strpos($html, '<x-public.division') ?: strpos($html, 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16'), strpos($html, 'cms-section--light'));
    }

    #[Test]
    public function on_any_other_page_a_section_goes_at_the_end_and_only_there(): void
    {
        $this->sync(['fields' => ['page' => '/faq', 'position' => 'after_news']]);

        $faq = $this->get('/faq')->assertOk()->getContent();
        $this->assertSame(1, substr_count($faq, 'cms-section--light'));
        $this->assertGreaterThan(strpos($faq, '</h1>'), strpos($faq, 'cms-section--light'));

        $this->get('/')->assertOk()->assertDontSee('ข้อความในคอลัมน์ที่สอง');
    }

    #[Test]
    public function a_draft_or_a_section_for_the_other_language_is_not_shown(): void
    {
        $this->sync(['status' => 'draft']);
        $this->get('/')->assertOk()->assertDontSee('cms-section', false);

        $this->sync(['status' => 'published', 'fields' => ['language' => 'en']]);
        PageSections::reset();
        $this->get('/')->assertOk()->assertDontSee('cms-section', false);
    }

    #[Test]
    public function removing_a_section_in_wordpress_removes_it_from_the_page(): void
    {
        $this->sync();
        (new SectionMapper(new MediaMirror(config('cms.media'))))->delete(500);

        $this->get('/')->assertOk()->assertDontSee('ข้อความในคอลัมน์ที่สอง');
    }

    #[Test]
    public function a_page_built_in_wordpress_has_its_own_address(): void
    {
        $page = $this->sync(['wp_post_id' => 600, 'post_type' => 'id5_page', 'title' => 'หน้าทดสอบ', 'slug' => 'visa-guide', 'fields' => ['title_en' => 'Test page']]);

        $this->assertSame(PageSection::PAGE, $page->kind);
        $this->get('/page/visa-guide')->assertOk()->assertSee('หน้าทดสอบ')->assertSee('ข้อความในคอลัมน์ที่สอง');

        // A Thai title gives WordPress no plain-letter slug: the number works.
        $this->sync(['wp_post_id' => 601, 'post_type' => 'id5_page', 'title' => 'หน้าภาษาไทย', 'slug' => '%e0%b8%ab%e0%b8%99%e0%b9%89%e0%b8%b2']);
        $this->get('/page/601')->assertOk()->assertSee('หน้าภาษาไทย');

        $this->get('/page/does-not-exist')->assertNotFound();
    }

    #[Test]
    public function a_visitor_gets_nothing_extra_and_an_editor_gets_an_add_button_at_each_slot(): void
    {
        $this->get('/')->assertOk()->assertDontSee('cms-section', false)->assertDontSee('id5_section', false);

        $html = $this->get('/?id5_edit='.$this->token())->assertOk()->getContent();

        foreach (['after_hero', 'after_services', 'after_news', 'bottom'] as $position) {
            $this->assertStringContainsString('data-cms-add="new:id5_section?id5_page=%2F&amp;id5_position='.$position.'"', $html);
        }

        // Other pages: only at the end.
        $faq = $this->get('/faq')->assertOk()->getContent();
        $this->assertSame(1, substr_count($faq, 'data-cms-add="new:id5_section?'));
        $this->assertStringContainsString('id5_page=%2Ffaq&amp;id5_position=bottom', $faq);
    }

    #[Test]
    public function in_edit_mode_a_section_has_its_pencil(): void
    {
        $this->sync();

        $this->get('/?id5_edit='.$this->token())->assertOk()->assertSee('data-cms-edit="post:500"', false);
    }
}
