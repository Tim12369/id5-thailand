<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Models\Faq;
use App\Models\ItaCard;
use App\Models\ItaSection;
use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\OrgChartTier;
use App\Services\Cms\CmsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Connecting WordPress to a website whose database was filled on its own.
 *
 * Such a site (the public demonstration, seeded twice) already has news, an
 * organisation chart and ITA topics. The first pull puts WordPress's copies
 * next to them. Retiring the rows no WordPress item owns is what leaves one of
 * each — and it must never run on an incomplete answer from WordPress.
 */
class RetireUnmanagedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled' => true, 'cms.base_url' => 'https://cms.test', 'cms.retries' => 0,
            'cms.auth.username' => 'sync', 'cms.auth.password' => 'app-password',
        ]);

        // What the seed data left behind.
        Article::create(['type' => 'news', 'title_th' => 'ข่าวจากข้อมูลตัวอย่าง', 'slug' => 'seed-news', 'body_th' => '', 'status' => 'published', 'is_public' => true, 'published_at' => now()]);
        Article::create(['type' => 'announcement', 'title_th' => 'ประกาศจากข้อมูลตัวอย่าง', 'slug' => 'seed-announcement', 'body_th' => '', 'status' => 'published', 'is_public' => true, 'published_at' => now()]);
        Faq::create(['question_th' => 'คำถามตัวอย่าง', 'answer_th' => 'คำตอบ', 'is_public' => true, 'is_active' => true, 'sort_order' => 1]);

        $unit = OrgChartSection::create(['slug' => 'seed-unit', 'name_th' => 'หน่วยตัวอย่าง', 'theme' => 'navy', 'sort_order' => 9, 'show_on_about_page' => true]);
        $row  = OrgChartTier::create(['section_id' => $unit->id, 'label_th' => 'แถวตัวอย่าง', 'sort_order' => 1]);
        OrgChartMember::create(['tier_id' => $row->id, 'name_th' => 'ตำแหน่งว่าง', 'sort_order' => 1]);

        $section = ItaSection::create(['section_number' => '1', 'title_th' => 'หมวดตัวอย่าง', 'sort_order' => 1, 'is_visible' => true]);
        ItaCard::create(['section_id' => $section->id, 'card_number' => '01', 'title_th' => 'หัวข้อตัวอย่าง', 'content_th' => 'เนื้อหา', 'sort_order' => 1]);
    }

    /** One item of each kind, as WordPress has them. */
    private function wordpressHas(array $only = null): void
    {
        $base = ['excerpt' => '', 'content' => '<p>เนื้อหา</p>', 'status' => 'published', 'menu_order' => 1, 'published_at' => '2026-03-01T03:00:00+00:00', 'image' => null, 'fields' => [], 'terms' => []];

        $items = [
            'post' => [['wp_post_id' => 101, 'post_type' => 'post', 'article_type' => 'news', 'title' => 'ข่าวจาก WordPress', 'slug' => 'wp-news'] + $base],
            'id5_announcement' => [['wp_post_id' => 102, 'post_type' => 'id5_announcement', 'article_type' => 'announcement', 'title' => 'ประกาศจาก WordPress', 'slug' => 'wp-announcement'] + $base],
            'id5_faq' => [['wp_post_id' => 103, 'post_type' => 'id5_faq', 'title' => 'คำถามจาก WordPress', 'slug' => 'wp-faq'] + $base],
            'id5_person' => [[
                'wp_post_id' => 104, 'post_type' => 'id5_person', 'title' => 'พ.ต.อ. จาก WordPress', 'slug' => 'wp-person',
                'fields' => ['tier_label_th' => 'ผู้บังคับการ', 'tier_order' => 1],
                'terms'  => ['id5_org_section' => [['term_id' => 9, 'name' => 'กองบังคับการ', 'slug' => 'division5', 'fields' => ['theme' => 'navy', 'sort_order' => 1, 'show_on_about_page' => true]]]],
            ] + $base],
            'id5_ita_card' => [[
                'wp_post_id' => 105, 'post_type' => 'id5_ita_card', 'title' => 'หัวข้อจาก WordPress', 'slug' => 'wp-ita',
                'fields' => ['card_number' => '01'],
                'terms'  => ['id5_ita_section' => [['term_id' => 20, 'name' => 'หมวดจาก WordPress', 'slug' => 'wp-section', 'fields' => ['section_number' => '1', 'sort_order' => 1, 'is_visible' => true]]]],
            ] + $base],
        ];

        Http::swap(new \Illuminate\Http\Client\Factory());

        $fakes = [];

        foreach (array_keys(config('cms.mappers')) as $type) {
            $fakes["cms.test/wp-json/id5/v1/collection/{$type}"] = ($only !== null && ! in_array($type, $only, true) && isset($items[$type]))
                ? Http::response('unavailable', 500)
                : Http::response(['post_type' => $type, 'items' => $items[$type] ?? []]);
        }

        Http::fake($fakes + ['cms.test/*' => Http::response(['settings' => [], 'menus' => [], 'texts' => [], 'terms' => []])]);
    }

    private function pull(): array
    {
        $sync    = CmsSyncService::make();
        $summary = [];

        foreach (array_keys(config('cms.mappers')) as $type) {
            try {
                $summary[$type] = $sync->syncType($type);
            } catch (\Throwable) {
                $summary[$type] = ['synced' => 0, 'skipped' => 0, 'failed' => 1];
            }
        }

        return [$sync, $summary];
    }

    #[Test]
    public function an_ordinary_pull_leaves_the_old_rows_next_to_the_wordpress_ones(): void
    {
        $this->wordpressHas();
        $this->pull();

        $this->assertSame(2, Article::where('type', 'news')->count());
        $this->assertSame(2, OrgChartMember::count());
        $this->assertSame(2, ItaCard::count());
    }

    #[Test]
    public function retiring_leaves_exactly_what_wordpress_has(): void
    {
        $this->wordpressHas();
        [$sync, $summary] = $this->pull();

        $retired = $sync->retireUnmanaged($summary);

        $this->assertSame(['news' => 1, 'announcement' => 1, 'faq' => 1, 'person' => 1, 'ita_topic' => 1], $retired);

        $this->assertSame(['ข่าวจาก WordPress'], Article::where('type', 'news')->pluck('title_th')->all());
        $this->assertSame(['ประกาศจาก WordPress'], Article::where('type', 'announcement')->pluck('title_th')->all());
        $this->assertSame(['คำถามจาก WordPress'], Faq::where('is_active', true)->pluck('question_th')->all());
        $this->assertSame(['พ.ต.อ. จาก WordPress'], OrgChartMember::pluck('name_th')->all());
        $this->assertSame(['ผู้บังคับการ'], OrgChartTier::pluck('label_th')->all());
        $this->assertSame(['กองบังคับการ'], OrgChartSection::pluck('name_th')->all());
        $this->assertSame(['หัวข้อจาก WordPress'], ItaCard::pluck('title_th')->all());
        $this->assertSame(['หมวดจาก WordPress'], ItaSection::pluck('title_th')->all());

        // News goes to the trash and the FAQ is only hidden: both can come back.
        $this->assertSame(2, Article::withTrashed()->where('type', 'news')->count());
        $this->assertSame(2, Faq::count());

        // And the public pages show one of each.
        $this->get('/news')->assertOk()->assertSee('ข่าวจาก WordPress')->assertDontSee('ข่าวจากข้อมูลตัวอย่าง');
        $this->get('/about')->assertOk()->assertSee('พ.ต.อ. จาก WordPress')->assertDontSee('หน่วยตัวอย่าง');

        // Doing it again changes nothing.
        $this->assertSame([], $sync->retireUnmanaged($summary));
    }

    #[Test]
    public function a_type_wordpress_could_not_answer_for_is_left_completely_alone(): void
    {
        // Only news came back; every other kind failed.
        $this->wordpressHas(['post']);
        [$sync, $summary] = $this->pull();

        $retired = $sync->retireUnmanaged($summary);

        $this->assertSame(['news' => 1], $retired);
        $this->assertSame(1, Article::where('type', 'announcement')->count());
        $this->assertSame(1, Faq::where('is_active', true)->count());
        $this->assertSame(1, OrgChartMember::count());
        $this->assertSame(1, ItaCard::count());
    }

    #[Test]
    public function a_type_wordpress_has_nothing_of_keeps_its_old_rows(): void
    {
        $this->wordpressHas();
        [$sync, $summary] = $this->pull();

        // A WordPress with no news at all is not a reason to hide the news.
        $summary['post'] = ['synced' => 0, 'skipped' => 0, 'failed' => 0];
        Article::where('wp_post_id', 101)->forceDelete();

        $sync->retireUnmanaged($summary);

        $this->assertSame(['ข่าวจากข้อมูลตัวอย่าง'], Article::where('type', 'news')->pluck('title_th')->all());
    }

    #[Test]
    public function wordpress_can_ask_for_everything_to_be_sent_again(): void
    {
        config(['cms.webhook.secret' => 'shared-secret', 'cms.sync.retire_unmanaged' => true]);
        $this->wordpressHas();

        $body      = json_encode(['event' => 'site.sync']);
        $timestamp = (string) time();

        $this->call('POST', '/cms/webhook', [], [], [], [
            'CONTENT_TYPE'         => 'application/json',
            'HTTP_X_ID5_TIMESTAMP' => $timestamp,
            'HTTP_X_ID5_SIGNATURE' => hash_hmac('sha256', $timestamp."\n".$body, 'shared-secret'),
        ], $body)->assertOk()->assertJson(['status' => 'accepted']);

        // The work happens after the reply has gone out.
        $this->app->terminate();

        $this->assertSame(['ข่าวจาก WordPress'], Article::where('type', 'news')->pluck('title_th')->all());
        $this->assertSame(['พ.ต.อ. จาก WordPress'], OrgChartMember::pluck('name_th')->all());
    }
}
