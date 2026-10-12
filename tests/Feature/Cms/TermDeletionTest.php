<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Models\Category;
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
 * Deleting a section or a category in WordPress must delete it on the
 * website. Posts announce their own deletion; a section has nothing left to
 * announce it, so the website compares its sections with WordPress's list.
 */
class TermDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled'       => true,
            'cms.base_url'      => 'https://cms.test',
            'cms.auth.username' => 'sync',
            'cms.auth.password' => 'app-password',
            'cms.retries'       => 0,
        ]);
    }

    private function wordpressHas(array $terms): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['cms.test/wp-json/id5/v1/terms' => Http::response(['terms' => $terms])]);
    }

    private function itaTerm(int $id, string $name, array $fields = []): array
    {
        return ['term_id' => $id, 'taxonomy' => 'id5_ita_section', 'name' => $name, 'slug' => 'ita-'.$id, 'fields' => $fields + ['section_number' => (string) $id, 'is_visible' => true]];
    }

    private function itaSection(int $termId, string $title): ItaSection
    {
        $section = ItaSection::create(['section_number' => (string) $termId, 'title_th' => $title, 'sort_order' => $termId, 'is_visible' => true]);
        $section->forceFill(['wp_term_id' => $termId])->save();

        return $section;
    }

    private function itaCard(ItaSection $section, int $wpPostId, string $title): ItaCard
    {
        $card = ItaCard::create(['section_id' => $section->id, 'card_number' => '01', 'title_th' => $title, 'content_th' => 'x', 'sort_order' => 1, 'is_visible' => true]);
        $card->forceFill(['wp_post_id' => $wpPostId])->save();

        return $card;
    }

    #[Test]
    public function an_ita_section_deleted_in_wordpress_leaves_the_website_with_its_topics(): void
    {
        $kept    = $this->itaSection(99, 'ค่านิยมหลัก');
        $deleted = $this->itaSection(100, 'ทดสอบ');
        $this->itaCard($kept, 252, 'คุณธรรม');
        $this->itaCard($deleted, 611, 'หัวข้อในหมวดที่ถูกลบ');

        $this->get('/ita')->assertOk()->assertSee('ทดสอบ')->assertSee('หัวข้อในหมวดที่ถูกลบ');

        $this->wordpressHas(['id5_ita_section' => [$this->itaTerm(99, 'ค่านิยมหลัก')]]);
        CmsSyncService::make()->syncTerms();

        $this->assertNull(ItaSection::where('wp_term_id', 100)->first());
        $this->assertSame(0, ItaCard::where('wp_post_id', 611)->count());
        $this->assertSame(1, ItaCard::where('wp_post_id', 252)->count());

        $this->get('/ita')->assertOk()->assertDontSee('หัวข้อในหมวดที่ถูกลบ')->assertSee('คุณธรรม');
    }

    #[Test]
    public function an_empty_ita_section_can_be_renamed_and_hidden_from_wordpress(): void
    {
        $this->itaSection(99, 'ชื่อเดิม');

        $this->wordpressHas(['id5_ita_section' => [$this->itaTerm(99, 'ชื่อใหม่', ['description_th' => 'คำอธิบาย', 'is_visible' => false])]]);
        CmsSyncService::make()->syncTerms();

        $section = ItaSection::where('wp_term_id', 99)->first();
        $this->assertSame('ชื่อใหม่', $section->title_th);
        $this->assertSame('คำอธิบาย', $section->description_th);
        $this->assertFalse((bool) $section->is_visible);
    }

    #[Test]
    public function nothing_is_removed_when_wordpress_s_answer_cannot_be_trusted(): void
    {
        $this->itaSection(99, 'ค่านิยมหลัก');

        // An older plugin that does not list this taxonomy at all.
        $this->wordpressHas(['category' => []]);
        CmsSyncService::make()->syncTerms();
        $this->assertSame(1, ItaSection::count());

        // A rebuilt WordPress that has no sections (yet).
        $this->wordpressHas(['id5_ita_section' => [], 'id5_org_section' => [], 'category' => [], 'id5_content_category' => []]);
        CmsSyncService::make()->syncTerms();
        $this->assertSame(1, ItaSection::count());
    }

    #[Test]
    public function a_section_never_linked_to_wordpress_is_left_alone(): void
    {
        ItaSection::create(['section_number' => '9', 'title_th' => 'หมวดเดิมก่อนใช้ WordPress', 'sort_order' => 9, 'is_visible' => true]);
        $this->itaSection(99, 'ค่านิยมหลัก');

        $this->wordpressHas(['id5_ita_section' => [$this->itaTerm(99, 'ค่านิยมหลัก')]]);
        CmsSyncService::make()->syncTerms();

        $this->assertSame(2, ItaSection::count());
    }

    #[Test]
    public function an_org_chart_unit_deleted_in_wordpress_leaves_with_its_people(): void
    {
        foreach ([[92, 'police', 'สำนักงานตำรวจแห่งชาติ'], [95, 'technology', 'ฝ่ายเทคโนโลยี']] as [$termId, $slug, $name]) {
            $section = OrgChartSection::create(['slug' => $slug, 'name_th' => $name, 'theme' => 'navy', 'sort_order' => $termId, 'show_on_about_page' => true]);
            $section->forceFill(['wp_term_id' => $termId])->save();

            $tier = OrgChartTier::create(['section_id' => $section->id, 'label_th' => 'หัวหน้า', 'sort_order' => 1]);
            $member = OrgChartMember::create(['tier_id' => $tier->id, 'name_th' => 'คุณ '.$slug, 'sort_order' => 1]);
            $member->forceFill(['wp_post_id' => $termId * 10])->save();
        }

        $this->wordpressHas(['id5_org_section' => [
            ['term_id' => 92, 'taxonomy' => 'id5_org_section', 'name' => 'สำนักงานตำรวจแห่งชาติ (ใหม่)', 'slug' => 'police', 'fields' => ['theme' => 'navy', 'show_on_about_page' => true]],
        ]]);
        CmsSyncService::make()->syncTerms();

        $this->assertSame(['สำนักงานตำรวจแห่งชาติ (ใหม่)'], OrgChartSection::pluck('name_th')->all());
        $this->assertSame(['คุณ police'], OrgChartMember::pluck('name_th')->all());
        $this->assertSame(1, OrgChartTier::count());
    }

    #[Test]
    public function a_category_deleted_in_wordpress_is_removed_and_its_articles_become_uncategorised(): void
    {
        $gone = Category::create(['type' => 'kb_public', 'slug' => 'old', 'name_th' => 'หมวดที่ถูกลบ', 'is_active' => true]);
        $gone->forceFill(['wp_term_id' => 500])->save();
        $kept = Category::create(['type' => 'kb_public', 'slug' => 'visa', 'name_th' => 'วีซ่า', 'is_active' => true]);
        $kept->forceFill(['wp_term_id' => 87])->save();
        $local = Category::create(['type' => 'kb_public', 'slug' => 'local', 'name_th' => 'ไม่เคยเชื่อมกับ WordPress', 'is_active' => true]);
        $internal = Category::create(['type' => 'sop', 'slug' => 'sop', 'name_th' => 'ใช้ภายใน', 'is_active' => true]);
        $internal->forceFill(['wp_term_id' => 501])->save();

        $article = Article::create([
            'type' => 'kb_public', 'title_th' => 'บทความ', 'slug' => 'article', 'body_th' => '<p>x</p>',
            'status' => 'published', 'is_public' => true, 'category_id' => $gone->id, 'published_at' => now(),
        ]);

        $this->wordpressHas(['id5_content_category' => [
            ['term_id' => 87, 'taxonomy' => 'id5_content_category', 'name' => 'วีซ่า', 'slug' => 'visa', 'fields' => ['name_en' => 'Visa']],
        ], 'category' => []]);
        CmsSyncService::make()->syncTerms();

        $this->assertNull(Category::find($gone->id));
        $this->assertNotNull(Category::find($kept->id));
        $this->assertNotNull(Category::find($local->id), 'a category WordPress never knew is not touched');
        $this->assertNotNull(Category::find($internal->id), 'categories of internal types are not touched');
        $this->assertNull($article->fresh()->category_id);
        $this->assertSame('published', $article->fresh()->status);
    }

    #[Test]
    public function a_topic_whose_section_was_deleted_leaves_the_website_and_returns_when_given_one(): void
    {
        $section = $this->itaSection(99, 'ค่านิยมหลัก');
        $this->itaCard($section, 611, 'หัวข้อ');

        $payload = [
            'wp_post_id' => 611, 'post_type' => 'id5_ita_card', 'title' => 'หัวข้อ', 'slug' => 'x', 'excerpt' => '', 'content' => '',
            'status' => 'published', 'menu_order' => 1, 'published_at' => '2026-03-01T03:00:00+00:00', 'image' => null,
            'fields' => ['card_number' => '01', 'content_th' => 'เนื้อหา', 'is_visible' => true],
            'terms'  => [],
        ];

        Http::fake(['cms.test/wp-json/id5/v1/post/611' => Http::sequence()
            ->push($payload)
            ->push(['terms' => ['id5_ita_section' => [$this->itaTerm(99, 'ค่านิยมหลัก')]]] + $payload)]);

        CmsSyncService::make()->syncPost('id5_ita_card', 611);
        $this->assertSame(0, ItaCard::where('wp_post_id', 611)->count());

        CmsSyncService::make()->syncPost('id5_ita_card', 611);
        $this->assertSame(1, ItaCard::where('wp_post_id', 611)->where('is_visible', true)->count());
    }
}
