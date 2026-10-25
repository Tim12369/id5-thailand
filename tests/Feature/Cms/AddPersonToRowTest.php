<?php

namespace Tests\Feature\Cms;

use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\OrgChartTier;
use App\Support\CmsEdit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * In edit mode every row of the organisation chart ends with an "add a person
 * to this row" tile. It carries the row's unit, name and position, so the form
 * it opens in WordPress needs nothing but the person's own details — and the
 * new card joins that row instead of starting another one.
 */
class AddPersonToRowTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'edit-mode-test-secret';

    private OrgChartSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cms.enabled' => true, 'cms.base_url' => 'https://cms.test', 'cms.webhook.secret' => self::SECRET]);

        $this->section = OrgChartSection::create(['slug' => 'police', 'name_th' => 'สำนักงานตำรวจแห่งชาติ', 'theme' => 'red_gold', 'sort_order' => 1, 'show_on_about_page' => true]);
        $this->section->forceFill(['wp_term_id' => 92])->save();

        foreach ([1 => 'ผู้บัญชาการ', 2 => 'รองผู้บัญชาการ & ที่ปรึกษา'] as $order => $label) {
            $tier = OrgChartTier::create(['section_id' => $this->section->id, 'label_th' => $label, 'label_en' => 'Row '.$order, 'sort_order' => $order]);
            OrgChartMember::create(['tier_id' => $tier->id, 'name_th' => 'พล.ต.อ. ทดสอบ '.$order, 'sort_order' => 1]);
        }
    }

    private function token(): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['n' => 'สมชาย', 'e' => time() + 3600])), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', 'id5-edit-mode|'.$payload, self::SECRET);
    }

    #[Test]
    public function a_visitor_never_sees_the_add_tiles(): void
    {
        $this->get('/about')->assertOk()
            ->assertSee('พล.ต.อ. ทดสอบ 1')
            ->assertDontSee('data-cms-add', false)
            ->assertDontSee('id5-add', false);
    }

    #[Test]
    public function in_edit_mode_each_row_ends_with_a_tile_that_knows_its_row(): void
    {
        $html = $this->get('/about?id5_edit='.$this->token())->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-cms-add="new:id5_person?'));

        // The unit, the row's name (safely encoded) and the row's position.
        $this->assertStringContainsString('id5_section=92&amp;id5_tier_label_th='.rawurlencode('ผู้บัญชาการ').'&amp;id5_tier_label_en=Row%201&amp;id5_tier_order=1"', $html);
        $this->assertStringContainsString('id5_tier_label_th='.rawurlencode('รองผู้บัญชาการ & ที่ปรึกษา').'&amp;id5_tier_label_en=Row%202&amp;id5_tier_order=2"', $html);
    }

    #[Test]
    public function a_unit_wordpress_does_not_know_gets_no_tile(): void
    {
        $this->section->forceFill(['wp_term_id' => null])->save();

        $this->get('/about?id5_edit='.$this->token())->assertOk()
            ->assertSee('id="id5-bar"', false)
            ->assertDontSee('data-cms-add="new:id5_person', false);

        $this->assertNull(CmsEdit::newPersonIn($this->section->fresh(), $this->section->tiers()->first()));
    }
}
