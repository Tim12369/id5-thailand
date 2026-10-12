<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Models\Faq;
use App\Models\PublicForm;
use App\Models\Station;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\ArticleMapper;
use App\Services\Cms\Mappers\FaqMapper;
use App\Services\Cms\Mappers\FormMapper;
use App\Services\Cms\Mappers\OfficeMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The mappers are where WordPress payloads become rows the public site reads,
 * so these cover the behaviour the migration depends on: Thai text survives,
 * re-syncing does not duplicate, and the columns this application owns are
 * never overwritten by WordPress.
 */
class ContentSyncTest extends TestCase
{
    use RefreshDatabase;

    private function media(): MediaMirror
    {
        return new MediaMirror(config('cms.media'));
    }

    /**
     * A payload shaped exactly like ID5_Serializer::post() produces.
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'wp_post_id'   => 101,
            'post_type'    => 'id5_announcement',
            'article_type' => 'announcement',
            'title'        => 'ประกาศ: ปิดทำการวันหยุดนักขัตฤกษ์',
            'slug'         => 'office-closure-notice',
            'excerpt'      => 'สรุปย่อภาษาไทย',
            'content'      => '<p>เนื้อหาภาษาไทย ทดสอบอักขระ ๑๒๓</p>',
            'status'       => 'published',
            'menu_order'   => 0,
            'published_at' => '2026-03-01T03:00:00+00:00',
            'image'        => null,
            'fields'       => [
                'title_en'        => 'Office closure notice',
                'excerpt_en'      => 'English summary',
                'body_en'         => '<p>English body</p>',
                'is_pinned'       => true,
                'expired_at'      => null,
                'seo_title'       => null,
                'seo_description' => null,
            ],
            'terms' => [],
        ], $overrides);
    }

    #[Test]
    public function it_stores_thai_text_without_corrupting_it(): void
    {
        (new ArticleMapper($this->media()))->upsert($this->payload());

        $article = Article::firstWhere('wp_post_id', 101);

        $this->assertSame('ประกาศ: ปิดทำการวันหยุดนักขัตฤกษ์', $article->title_th);
        $this->assertStringContainsString('ทดสอบอักขระ ๑๒๓', $article->body_th);
        $this->assertSame('Office closure notice', $article->title_en);
    }

    #[Test]
    public function it_preserves_the_slug_because_it_is_a_public_url(): void
    {
        (new ArticleMapper($this->media()))->upsert($this->payload());

        $this->assertSame('office-closure-notice', Article::firstWhere('wp_post_id', 101)->slug);
    }

    #[Test]
    public function a_thai_title_gets_a_readable_url_from_the_english_title(): void
    {
        // What WordPress produces for a Thai title: a percent-encoded slug.
        (new ArticleMapper($this->media()))->upsert($this->payload([
            'slug' => '%e0%b8%9b%e0%b8%a3%e0%b8%b0%e0%b8%81%e0%b8%b2%e0%b8%a8',
        ]));

        $this->assertSame('office-closure-notice', Article::firstWhere('wp_post_id', 101)->slug);
    }

    #[Test]
    public function a_thai_title_with_no_english_title_still_gets_a_clean_url(): void
    {
        (new ArticleMapper($this->media()))->upsert($this->payload([
            'slug'   => '%e0%b8%9b%e0%b8%a3%e0%b8%b0%e0%b8%81%e0%b8%b2%e0%b8%a8',
            'fields' => ['title_en' => null],
        ]));

        $this->assertSame('announcement-101', Article::firstWhere('wp_post_id', 101)->slug);
    }

    #[Test]
    public function a_published_url_does_not_change_when_the_post_is_trashed_and_restored(): void
    {
        $mapper = new ArticleMapper($this->media());
        $mapper->upsert($this->payload());

        // WordPress renames the slug of a trashed post.
        $mapper->upsert($this->payload(['slug' => 'office-closure-notice__trashed', 'status' => 'archived']));

        $this->assertSame('office-closure-notice', Article::firstWhere('wp_post_id', 101)->slug);
    }

    #[Test]
    public function a_new_office_with_a_thai_name_gets_its_url_from_its_code_and_english_name(): void
    {
        (new OfficeMapper($this->media()))->upsert([
            'wp_post_id' => 310,
            'title'      => 'ตรวจคนเข้าเมืองจังหวัดลำปาง',
            'slug'       => '%e0%b8%95%e0%b8%a3%e0%b8%a7%e0%b8%88',
            'status'     => 'published',
            'menu_order' => 1,
            'image'      => null,
            'fields'     => ['code' => 'LP01', 'name_en' => 'Lampang Immigration', 'is_active' => true],
            'terms'      => [],
        ]);

        $this->assertSame('lp01-lampang-immigration', Station::firstWhere('code', 'LP01')->slug);
    }

    #[Test]
    public function it_updates_the_same_row_when_the_same_post_syncs_twice(): void
    {
        $mapper = new ArticleMapper($this->media());

        $mapper->upsert($this->payload());
        $mapper->upsert($this->payload(['title' => 'ประกาศฉบับแก้ไข']));

        $this->assertSame(1, Article::count());
        $this->assertSame('ประกาศฉบับแก้ไข', Article::firstWhere('wp_post_id', 101)->title_th);
    }

    #[Test]
    public function it_does_not_reset_the_public_view_counter(): void
    {
        $mapper = new ArticleMapper($this->media());

        $mapper->upsert($this->payload());

        // view_count is not fillable by design, so it is set directly — the
        // point of the test is that the sync leaves it alone.
        $article = Article::firstWhere('wp_post_id', 101);
        $article->view_count = 523;
        $article->save();

        $mapper->upsert($this->payload(['title' => 'แก้ไขแล้ว']));

        $this->assertSame(523, $article->fresh()->view_count);
    }

    #[Test]
    public function a_draft_in_wordpress_is_not_published_on_the_public_site(): void
    {
        (new ArticleMapper($this->media()))->upsert($this->payload(['status' => 'draft']));

        $this->assertSame(0, Article::published()->public()->count());
    }

    #[Test]
    public function it_gives_a_colliding_slug_a_distinct_url_instead_of_failing(): void
    {
        $mapper = new ArticleMapper($this->media());

        $mapper->upsert($this->payload());
        $mapper->upsert($this->payload(['wp_post_id' => 202]));

        $this->assertSame(2, Article::count());
        $this->assertSame(2, Article::distinct()->count('slug'));
    }

    #[Test]
    public function deleting_a_post_in_wordpress_hides_the_article_but_keeps_the_row(): void
    {
        $mapper = new ArticleMapper($this->media());
        $mapper->upsert($this->payload());

        $mapper->delete(101);

        $this->assertSame(0, Article::count());
        $this->assertSame(1, Article::withTrashed()->count());
    }

    #[Test]
    public function an_faq_draft_becomes_inactive_rather_than_disappearing(): void
    {
        $mapper = new FaqMapper($this->media());

        $mapper->upsert([
            'wp_post_id' => 55,
            'title'      => 'ต้องแจ้ง ตม.30 ภายในกี่วัน?',
            'content'    => '<p>ภายใน 24 ชั่วโมง</p>',
            'status'     => 'draft',
            'menu_order' => 3,
            'image'      => null,
            'fields'     => ['question_en' => null, 'answer_en' => null],
            'terms'      => [],
        ]);

        $faq = Faq::firstWhere('wp_post_id', 55);

        $this->assertFalse($faq->is_active);
        $this->assertSame(3, $faq->sort_order);
        $this->assertSame('ต้องแจ้ง ตม.30 ภายในกี่วัน?', $faq->question_th);
    }

    #[Test]
    public function an_office_keeps_its_operational_columns_and_identity(): void
    {
        // A station that already exists, carrying live Command Center state and
        // a geocoded pin, as it would at the moment of cut-over.
        $station = Station::create([
            'code'            => 'IMM-CM',
            'slug'            => 'imm-cm',
            'name_th'         => 'ตม.เชียงใหม่',
            'name_en'         => 'Chiang Mai',
            'province'        => 'เชียงใหม่',
            'region'          => 'ภาคเหนือ',
            'lat'             => 18.7883,
            'lng'             => 98.9853,
            'is_active'       => true,
            'ops_status'      => 'warning',
            'active_officers' => 12,
            'online_cameras'  => 4,
            'active_incidents'=> 2,
        ]);

        (new OfficeMapper($this->media()))->upsert([
            'wp_post_id' => 300,
            'title'      => 'ตรวจคนเข้าเมืองจังหวัดเชียงใหม่',
            'slug'       => 'imm-cm',
            'status'     => 'published',
            'menu_order' => 5,
            'image'      => null,
            'fields'     => [
                'code'       => 'IMM-CM',
                'name_en'    => 'Chiang Mai Immigration',
                'phone'      => '053-000000',
                'is_active'  => true,
                // Coordinates left blank by the editor.
                'lat'        => null,
                'lng'        => null,
                'hours_mon'  => '08:30-16:30',
            ],
            'terms' => [],
        ]);

        $station->refresh();

        // Adopted, not duplicated — the Command Center's foreign keys survive.
        $this->assertSame(1, Station::count());
        $this->assertSame(300, (int) $station->wp_post_id);

        // Editorial fields updated...
        $this->assertSame('ตรวจคนเข้าเมืองจังหวัดเชียงใหม่', $station->name_th);
        $this->assertSame(['mon' => '08:30-16:30'], $station->opening_hours);

        // ...operational fields untouched.
        $this->assertSame('warning', $station->ops_status);
        $this->assertSame(12, $station->active_officers);
        $this->assertSame(4, $station->online_cameras);
        $this->assertSame(2, $station->active_incidents);

        // A blank coordinate does not wipe the existing pin.
        $this->assertEqualsWithDelta(18.7883, $station->lat, 0.0001);
        $this->assertEqualsWithDelta(98.9853, $station->lng, 0.0001);
    }

    #[Test]
    public function a_draft_form_reusing_a_code_cannot_touch_the_real_form(): void
    {
        $mapper = new FormMapper($this->media());

        $real = PublicForm::create([
            'code' => 'TM30', 'name_th' => 'แบบแจ้งที่พัก', 'name_en' => 'TM30', 'category' => 'reporting',
            'path' => 'forms/tm30.pdf', 'disk' => 'local', 'is_active' => true, 'download_count' => 40,
        ]);
        $real->forceFill(['wp_post_id' => 500])->save();

        $result = $mapper->upsert([
            'wp_post_id' => 501,
            'title'      => 'แบบฟอร์มรหัสซ้ำ',
            'status'     => 'draft',
            'menu_order' => 0,
            'image'      => null,
            'fields'     => ['code' => 'TM30', 'is_active' => true],
            'terms'      => [],
        ]);

        $this->assertNull($result);
        $this->assertSame(1, PublicForm::count());

        $real->refresh();
        $this->assertSame('แบบแจ้งที่พัก', $real->name_th);
        $this->assertTrue($real->is_active);
        $this->assertSame(500, (int) $real->wp_post_id);
    }

    #[Test]
    public function a_form_without_a_code_is_skipped_rather_than_guessed_at(): void
    {
        $result = (new FormMapper($this->media()))->upsert([
            'wp_post_id' => 400,
            'title'      => 'แบบฟอร์มไม่มีรหัส',
            'status'     => 'published',
            'menu_order' => 0,
            'image'      => null,
            'fields'     => ['code' => null],
            'terms'      => [],
        ]);

        $this->assertNull($result);
        $this->assertSame(0, PublicForm::count());
    }

    #[Test]
    public function an_unknown_form_category_falls_back_to_other_rather_than_hiding_the_form(): void
    {
        (new FormMapper($this->media()))->upsert([
            'wp_post_id' => 401,
            'title'      => 'แบบฟอร์มทดสอบ',
            'status'     => 'published',
            'menu_order' => 0,
            'image'      => null,
            'fields'     => ['code' => 'TM99', 'is_active' => true],
            'terms'      => ['id5_form_category' => [['slug' => 'invented-category', 'name' => 'ไม่รู้จัก', 'fields' => []]]],
        ]);

        $this->assertSame('other', PublicForm::firstWhere('code', 'TM99')->category);
    }

    #[Test]
    public function a_form_with_no_file_yet_is_not_offered_for_download(): void
    {
        (new FormMapper($this->media()))->upsert([
            'wp_post_id' => 402,
            'title'      => 'แบบฟอร์มที่ยังไม่ได้แนบไฟล์',
            'status'     => 'published',
            'menu_order' => 0,
            'image'      => null,
            'fields'     => ['code' => 'TM98', 'is_active' => true, 'file' => null],
            'terms'      => [],
        ]);

        $form = PublicForm::firstWhere('code', 'TM98');

        $this->assertNotNull($form, 'the form should still be recorded');
        $this->assertFalse($form->is_active, 'but never offered without a file');
        $this->assertSame(0, PublicForm::active()->count());
    }
}
