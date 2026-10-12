<?php

namespace Tests\Feature\Cms;

use App\Models\GuideTopic;
use App\Models\ItaCard;
use App\Models\SmartGuide;
use App\Services\Cms\Mappers\GuideContentMapper;
use App\Services\Cms\Mappers\GuideTopicMapper;
use App\Services\Cms\Mappers\ItaCardMapper;
use App\Services\Cms\MediaMirror;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the shape of content as it crosses from WordPress into the tables the
 * public site reads.
 *
 * Each test pins a defect that a field-by-field comparison against the
 * pre-migration data actually found: formatting stripped from Smart Guide
 * answers, HTML leaking onto a page that prints plain text, and list ordering
 * shifting by one.
 */
class ContentFidelityTest extends TestCase
{
    use RefreshDatabase;

    private function media(): MediaMirror
    {
        return new MediaMirror(config('cms.media'));
    }

    private function guidance(array $fields, string $status = 'published'): array
    {
        return [
            'wp_post_id' => 900,
            'post_type'  => 'id5_guide_content',
            'title'      => 'นักท่องเที่ยว — วีซ่า',
            'status'     => $status,
            'menu_order' => 0,
            'image'      => null,
            'fields'     => array_merge(['persona' => 'tourist', 'topic_key' => 'visa'], $fields),
            'terms'      => [],
        ];
    }

    #[Test]
    public function smart_guide_answers_keep_their_formatting(): void
    {
        $html = '<p><strong>เอกสารที่ต้องใช้:</strong></p><ul><li>หนังสือเดินทาง</li><li>รูปถ่าย 4x6 cm</li></ul>';

        (new GuideContentMapper($this->media()))->upsert($this->guidance(['what_th' => $html, 'what_en' => '<p>Passport</p>']));

        $row = SmartGuide::where(['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'what'])->first();

        // Byte for byte: the public page prints this as HTML, so losing the
        // tags runs every list item together into one unreadable line.
        $this->assertSame($html, $row->content_th);
        $this->assertSame('<p>Passport</p>', $row->content_en);
    }

    #[Test]
    public function one_guidance_entry_becomes_one_row_per_answered_question(): void
    {
        (new GuideContentMapper($this->media()))->upsert($this->guidance([
            'who_th'   => '<p>ใคร</p>',
            'where_th' => '<p>ที่ไหน</p>',
        ]));

        $this->assertSame(
            ['where', 'who'],
            SmartGuide::where('topic', 'visa')->orderBy('dimension')->pluck('dimension')->all()
        );
    }

    #[Test]
    public function clearing_an_answer_removes_it_from_the_public_page(): void
    {
        $mapper = new GuideContentMapper($this->media());

        $mapper->upsert($this->guidance(['who_th' => '<p>ใคร</p>', 'why_th' => '<p>ทำไม</p>']));
        $mapper->upsert($this->guidance(['who_th' => '<p>ใคร</p>', 'why_th' => null]));

        $this->assertSame(['who'], SmartGuide::where('topic', 'visa')->pluck('dimension')->all());
    }

    #[Test]
    public function unpublished_guidance_is_hidden_and_deleted_guidance_is_recoverable(): void
    {
        $mapper = new GuideContentMapper($this->media());

        $mapper->upsert($this->guidance(['who_th' => '<p>ใคร</p>'], 'draft'));
        $this->assertSame(0, SmartGuide::active()->count());

        $mapper->upsert($this->guidance(['who_th' => '<p>ใคร</p>']));
        $this->assertSame(1, SmartGuide::active()->count());

        $mapper->delete(900);
        $this->assertSame(0, SmartGuide::active()->count());
        $this->assertSame(1, SmartGuide::count(), 'the row is hidden, not destroyed');
    }

    #[Test]
    public function an_unpublished_duplicate_never_overwrites_the_real_entry(): void
    {
        $mapper = new GuideContentMapper($this->media());

        // The real, published entry.
        $mapper->upsert($this->guidance(['who_th' => '<p>ของจริง</p>', 'why_th' => '<p>เหตุผล</p>']));

        // Someone creates a second entry for the same audience + topic.
        // WordPress keeps it as a draft, but a draft is still synced.
        $duplicate = $this->guidance(['who_th' => '<p>ของซ้ำ</p>'], 'draft');
        $duplicate['wp_post_id'] = 901;

        $this->assertNull($mapper->upsert($duplicate));

        $rows = SmartGuide::where('topic', 'visa')->orderBy('dimension')->get();
        $this->assertSame(['who', 'why'], $rows->pluck('dimension')->all(), 'no answer was deleted');
        $this->assertSame('<p>ของจริง</p>', $rows->firstWhere('dimension', 'who')->content_th);
        $this->assertTrue($rows->every(fn ($r) => $r->is_active), 'and the entry is still live');
        $this->assertSame([900], $rows->pluck('wp_post_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
    }

    #[Test]
    public function a_published_replacement_may_take_over_once_the_original_is_gone(): void
    {
        $mapper = new GuideContentMapper($this->media());
        $mapper->upsert($this->guidance(['who_th' => '<p>ของเดิม</p>']));

        // The original was deleted in WordPress and a new entry published.
        $mapper->delete(900);
        $replacement = $this->guidance(['who_th' => '<p>ของใหม่</p>']);
        $replacement['wp_post_id'] = 902;
        $mapper->upsert($replacement);

        $row = SmartGuide::where(['topic' => 'visa', 'dimension' => 'who'])->first();
        $this->assertSame('<p>ของใหม่</p>', $row->content_th);
        $this->assertTrue($row->is_active);
        $this->assertSame(902, (int) $row->wp_post_id);
    }

    #[Test]
    public function guidance_for_an_unknown_audience_is_refused(): void
    {
        $payload = $this->guidance(['who_th' => '<p>x</p>']);
        $payload['fields']['persona'] = 'not-a-real-persona';

        $this->assertNull((new GuideContentMapper($this->media()))->upsert($payload));
        $this->assertSame(0, SmartGuide::count());
    }

    #[Test]
    public function ita_content_is_stored_as_plain_text(): void
    {
        (new ItaCardMapper($this->media()))->upsert([
            'wp_post_id' => 700,
            'title'      => 'คุณธรรม',
            'status'     => 'published',
            'menu_order' => 1,
            'image'      => null,
            'fields'     => [
                'card_number' => '01',
                // Even if HTML arrives, the public ITA page prints this value
                // escaped — so tags must never reach the column.
                'content_th'  => '<p>บรรทัดแรก</p><p>บรรทัดที่สอง &amp; ต่อ</p>',
                'content_en'  => 'Plain already',
                'is_visible'  => true,
            ],
            'terms' => ['id5_ita_section' => [['term_id' => 5, 'name' => 'ค่านิยมหลัก', 'slug' => 'core', 'fields' => []]]],
        ]);

        $card = ItaCard::firstWhere('wp_post_id', 700);

        $this->assertSame("บรรทัดแรก\nบรรทัดที่สอง & ต่อ", $card->content_th);
        $this->assertSame('Plain already', $card->content_en);
        $this->assertStringNotContainsString('<', $card->content_th);
    }

    #[Test]
    public function guide_lists_keep_their_one_based_order(): void
    {
        (new GuideTopicMapper($this->media()))->upsert([
            'wp_post_id' => 800,
            'title'      => 'วีซ่า',
            'status'     => 'published',
            'menu_order' => 1,
            'image'      => null,
            'fields'     => [
                'topic_key' => 'visa',
                'is_active' => true,
                'documents' => [
                    ['name_th' => 'หนังสือเดินทาง', 'name_en' => 'Passport', 'is_required' => true],
                    ['name_th' => 'รูปถ่าย', 'name_en' => 'Photo', 'is_required' => true],
                ],
                'steps'     => [
                    ['title_th' => 'ขั้นแรก', 'title_en' => 'First'],
                    ['title_th' => 'ขั้นสอง', 'title_en' => 'Second'],
                ],
                'locations' => [['type' => 'online', 'label_th' => 'ระบบ e-Visa', 'label_en' => 'e-Visa']],
            ],
            'terms' => [],
        ]);

        $topic = GuideTopic::with(['documents', 'steps', 'locations'])->firstWhere('topic', 'visa');

        $this->assertSame([1, 2], $topic->documents->pluck('sort_order')->all());
        $this->assertSame([1, 2], $topic->steps->pluck('step_number')->all());
        $this->assertSame([1, 2], $topic->steps->pluck('sort_order')->all());
        $this->assertSame([1], $topic->locations->pluck('sort_order')->all());
    }
}
