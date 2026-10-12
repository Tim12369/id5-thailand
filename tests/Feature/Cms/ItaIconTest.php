<?php

namespace Tests\Feature\Cms;

use App\Models\ItaCard;
use App\Models\ItaSection;
use App\Services\Cms\CmsSyncService;
use App\Support\ItaIcon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An ITA topic shows a picture the editor uploaded, or the icon they named, or
 * — when they gave neither — an icon chosen to suit what the topic is about.
 * It is never left with nothing, and never with an icon the font cannot draw.
 */
class ItaIconTest extends TestCase
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

        Storage::fake('public');
    }

    private function payload(array $fields = [], string $title = 'งบประมาณประจำปี'): array
    {
        return [
            'wp_post_id' => 700, 'post_type' => 'id5_ita_card', 'title' => $title, 'slug' => 'x', 'excerpt' => '', 'content' => '',
            'status' => 'published', 'menu_order' => 1, 'published_at' => '2026-03-01T03:00:00+00:00', 'image' => null,
            'fields' => $fields + ['card_number' => '01', 'content_th' => 'เนื้อหา', 'is_visible' => true, 'icon' => '', 'icon_image' => null],
            'terms'  => ['id5_ita_section' => [[
                'term_id' => 99, 'taxonomy' => 'id5_ita_section', 'name' => 'ค่านิยมหลัก', 'slug' => 'core',
                'fields'  => ['section_number' => '1', 'is_visible' => true],
            ]]],
        ];
    }

    private function sync(array $payload): ItaCard
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'cms.test/wp-json/id5/v1/post/700'  => Http::response($payload),
            'cms.test/wp-content/uploads/*'     => Http::response('PNG-BYTES', 200, ['Content-Type' => 'image/png']),
        ]);

        CmsSyncService::make()->syncPost('id5_ita_card', 700);

        return ItaCard::where('wp_post_id', 700)->firstOrFail();
    }

    // ─── Choosing an icon from the wording ────────────────────────────────

    #[Test]
    public function every_icon_the_rules_can_choose_exists_in_the_site_s_icon_font(): void
    {
        $this->assertGreaterThan(1000, count(ItaIcon::available()), 'the icon font could not be read');
        $this->assertSame([], array_values(array_diff(ItaIcon::candidates(), ItaIcon::available())));
    }

    #[Test]
    public function the_icon_suits_what_the_topic_is_about_in_thai_or_english(): void
    {
        foreach ([
            'งบประมาณประจำปี'                 => 'coin',
            'การจัดซื้อจัดจ้าง'                 => 'shopping-cart',
            'ช่องทางการร้องเรียน'              => 'message-report',
            'โครงสร้างหน่วยงาน'                => 'sitemap',
            'ต่อต้านการทุจริต'                  => 'gavel',
            'ความโปร่งใส'                      => 'eye-check',
            'คุณธรรม'                          => 'shield-check',
            'นโยบายคุ้มครองข้อมูลส่วนบุคคล'      => 'shield-lock',
            'No Gift Policy'                  => 'gift-off',
            'Annual Budget Plan'              => 'coin',
            'Organization Chart'              => 'sitemap',
            'หัวข้อที่ไม่เข้ากับคำใดเลย'          => ItaIcon::FALLBACK,
        ] as $title => $icon) {
            $this->assertSame($icon, ItaIcon::suggest($title), $title);
        }

        // The English title counts too, and nothing at all still gives an icon.
        $this->assertSame('coin', ItaIcon::suggest('หัวข้อ', 'Budget'));
        $this->assertSame(ItaIcon::FALLBACK, ItaIcon::suggest(null, ''));
    }

    #[Test]
    public function what_an_editor_types_is_accepted_in_the_forms_people_actually_use(): void
    {
        $this->assertSame('file-text', ItaIcon::normalise('file-text'));
        $this->assertSame('file-text', ItaIcon::normalise('ti-file-text'));
        $this->assertSame('eye-check', ItaIcon::normalise(' ti ti-Eye-Check '));

        foreach (['idk', '', null, 'file text', '<script>', 'x" onload="y'] as $notAnIcon) {
            $this->assertNull(ItaIcon::normalise($notAnIcon));
        }
    }

    // ─── What reaches the website ─────────────────────────────────────────

    #[Test]
    public function a_topic_with_no_icon_and_no_picture_is_given_one_automatically(): void
    {
        $card = $this->sync($this->payload());

        $this->assertNull($card->icon, 'nothing is stored, so renaming the topic changes its icon');
        $this->assertSame('coin', $card->display_icon);

        $this->get('/ita?lang=th')->assertOk()->assertSee('ti ti-coin', false);

        $card = $this->sync($this->payload([], 'ช่องทางการร้องเรียน'));
        $this->assertSame('message-report', $card->display_icon);
    }

    #[Test]
    public function an_icon_the_editor_named_wins_over_the_automatic_one(): void
    {
        $card = $this->sync($this->payload(['icon' => 'ti-heart']));

        $this->assertSame('heart', $card->icon);
        $this->get('/ita?lang=th')->assertOk()->assertSee('ti ti-heart', false)->assertDontSee('ti ti-coin', false)->assertDontSee('ti-ti-', false);
    }

    #[Test]
    public function a_name_that_is_not_an_icon_falls_back_to_the_automatic_one(): void
    {
        $card = $this->sync($this->payload(['icon' => 'idk']));

        $this->assertNull($card->icon);
        $this->get('/ita?lang=th')->assertOk()->assertSee('ti ti-coin', false)->assertDontSee('ti-idk', false);
    }

    #[Test]
    public function an_old_row_holding_a_bad_icon_name_still_draws_an_icon(): void
    {
        $section = ItaSection::create(['section_number' => '1', 'title_th' => 'หมวด', 'sort_order' => 1, 'is_visible' => true]);
        ItaCard::create(['section_id' => $section->id, 'card_number' => '01', 'title_th' => 'การจัดซื้อจัดจ้าง', 'content_th' => 'x', 'icon' => 'idk', 'sort_order' => 1, 'is_visible' => true]);

        $this->get('/ita?lang=th')->assertOk()->assertSee('ti ti-shopping-cart', false)->assertDontSee('ti-idk', false);
    }

    #[Test]
    public function an_uploaded_picture_is_copied_to_the_website_and_shown_instead_of_the_icon(): void
    {
        $card = $this->sync($this->payload(['icon_image' => [
            'id' => 55, 'url' => 'https://cms.test/wp-content/uploads/2026/10/budget.png', 'filename' => 'budget.png', 'mime_type' => 'image/png', 'size' => 9, 'alt' => '',
        ]]));

        $this->assertNotNull($card->icon_image);
        Storage::disk('public')->assertExists($card->icon_image);
        $this->assertSame('PNG-BYTES', Storage::disk('public')->get($card->icon_image));

        $this->get('/ita?lang=th')
            ->assertOk()
            ->assertSee('storage/'.$card->icon_image, false)
            ->assertDontSee('ti ti-coin', false);
    }

    #[Test]
    public function removing_the_picture_in_wordpress_brings_the_icon_back(): void
    {
        $picture = ['id' => 55, 'url' => 'https://cms.test/wp-content/uploads/2026/10/budget.png', 'filename' => 'budget.png', 'mime_type' => 'image/png', 'size' => 9, 'alt' => ''];

        $this->assertNotNull($this->sync($this->payload(['icon_image' => $picture]))->icon_image);

        $card = $this->sync($this->payload(['icon_image' => null]));

        $this->assertNull($card->icon_image);
        $this->get('/ita?lang=th')->assertOk()->assertSee('ti ti-coin', false)->assertDontSee('<img src="'.url('storage/cms'), false);
    }

    #[Test]
    public function a_file_that_is_not_a_picture_is_not_shown(): void
    {
        $card = $this->sync($this->payload(['icon_image' => [
            'id' => 56, 'url' => 'https://cms.test/wp-content/uploads/2026/10/evil.php', 'filename' => 'evil.php', 'mime_type' => 'application/x-php', 'size' => 9, 'alt' => '',
        ]]));

        $this->assertNull($card->icon_image);
        Storage::disk('public')->assertDirectoryEmpty('cms');
    }
}
