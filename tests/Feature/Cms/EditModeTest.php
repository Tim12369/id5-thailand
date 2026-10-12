<?php

namespace Tests\Feature\Cms;

use App\Models\HomeDivisionCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edit mode draws pencils and a WordPress-style bar on the public website for a
 * logged-in editor. The property that matters most is the negative one: nobody
 * without a token WordPress signed may ever see any of it.
 */
class EditModeTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'edit-mode-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled'        => true,
            'cms.base_url'       => 'https://cms.test',
            'cms.webhook.secret' => self::SECRET,
        ]);

        $card = HomeDivisionCard::create(['key' => 'technology', 'title_th' => 'ฝ่ายเทคโนโลยี', 'sort_order' => 3]);
        $card->forceFill(['wp_post_id' => 259])->save();
    }

    /** A token exactly as the WordPress plugin issues it. */
    private function token(int $expires, string $secret = self::SECRET, string $prefix = 'id5-edit-mode|'): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['n' => 'สมชาย', 'e' => $expires])), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', $prefix.$payload, $secret);
    }

    private function assertNoEditMode($response): void
    {
        $response->assertOk()
            ->assertDontSee('data-cms-edit', false)
            ->assertDontSee('data-cms-add', false)
            ->assertDontSee('id5-bar', false)
            ->assertDontSee('id5-pencil', false)
            ->assertDontSee('cms.test', false);
    }

    #[Test]
    public function an_ordinary_visitor_gets_no_edit_mode_output_at_all(): void
    {
        $this->assertNoEditMode($this->get('/'));
    }

    #[Test]
    public function a_valid_token_turns_on_the_bar_and_marks_what_is_editable(): void
    {
        $response = $this->get('/?id5_edit='.$this->token(time() + 3600));

        $response->assertOk()
            ->assertSee('id="id5-bar"', false)
            ->assertSee('data-cms-edit="post:259"', false)          // the homepage card
            ->assertSee('data-cms-edit="setting:hero"', false)      // the banner
            ->assertSee('data-cms-edit="setting:footer"', false)
            ->assertSee('สวัสดี สมชาย');

        // A page with pencils on it must never be cached for a visitor, and may
        // only be framed by the WordPress install.
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame("frame-ancestors 'self' https://cms.test", $response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function the_news_announcement_and_alert_lists_offer_to_add_one_even_when_empty(): void
    {
        $this->get('/?id5_edit='.$this->token(time() + 3600))->assertOk()
            ->assertSee('data-cms-add="new:post"', false)
            ->assertSee('data-cms-add="new:id5_announcement"', false)
            ->assertSee('data-cms-add="new:id5_alert"', false);

        $this->get('/news')->assertOk()->assertSee('data-cms-add="new:post"', false)->assertSee('เพิ่มข่าว');
        $this->get('/news?tab=announcements')->assertOk()->assertSee('data-cms-add="new:id5_announcement"', false);
        $this->get('/news?tab=alerts')->assertOk()->assertSee('data-cms-add="new:id5_alert"', false);
        $this->get('/announcements')->assertOk()->assertSee('data-cms-add="new:id5_announcement"', false)->assertSee('เพิ่มประกาศ');
        $this->get('/alerts')->assertOk()->assertSee('data-cms-add="new:id5_alert"', false)->assertSee('เพิ่มการแจ้งเตือน');

        // And a visitor to the same empty lists sees none of it.
        $this->get('/edit/exit');
        foreach (['/', '/news', '/announcements', '/alerts'] as $path) {
            $this->assertNoEditMode($this->get($path));
        }
    }

    #[Test]
    public function edit_mode_is_remembered_while_browsing_and_can_be_switched_off(): void
    {
        $this->get('/?id5_edit='.$this->token(time() + 3600))->assertSee('id="id5-bar"', false);
        $this->get('/faq')->assertSee('id="id5-bar"', false);

        $this->get('/edit/exit')->assertRedirect('/');
        $this->assertNoEditMode($this->get('/'));
    }

    #[Test]
    public function an_expired_token_is_refused(): void
    {
        $this->assertNoEditMode($this->get('/?id5_edit='.$this->token(time() - 1)));
    }

    #[Test]
    public function a_token_signed_with_the_wrong_secret_is_refused(): void
    {
        $this->assertNoEditMode($this->get('/?id5_edit='.$this->token(time() + 3600, 'not-the-secret')));
    }

    #[Test]
    public function a_webhook_style_signature_cannot_be_reused_as_an_edit_token(): void
    {
        // Same secret, but signed without the purpose prefix — as a captured
        // webhook signature would be.
        $this->assertNoEditMode($this->get('/?id5_edit='.$this->token(time() + 3600, self::SECRET, '')));
    }

    #[Test]
    public function garbage_in_the_token_parameter_is_harmless(): void
    {
        foreach (['x', 'a.b', '..', str_repeat('A', 5000), '<script>alert(1)</script>.abc'] as $junk) {
            $this->assertNoEditMode($this->get('/?id5_edit='.urlencode($junk)));
        }
    }

    #[Test]
    public function edit_mode_is_impossible_when_the_integration_is_switched_off(): void
    {
        config(['cms.enabled' => false]);

        $this->assertNoEditMode($this->get('/?id5_edit='.$this->token(time() + 3600)));
    }

    #[Test]
    public function content_not_managed_in_wordpress_gets_no_pencil(): void
    {
        HomeDivisionCard::query()->update(['wp_post_id' => null]);

        $this->get('/?id5_edit='.$this->token(time() + 3600))
            ->assertOk()
            ->assertDontSee('data-cms-edit="post:', false);
    }

    #[Test]
    public function the_edit_shortcut_and_wordpress_addresses_lead_to_wordpress(): void
    {
        $this->get('/edit')->assertRedirect('https://cms.test/wp-admin/admin.php?page=id5-site-editor&path=%2F');
        $this->get('/wp-admin/')->assertRedirect('https://cms.test/wp-admin/');
        $this->get('/wp-login.php')->assertRedirect('https://cms.test/wp-admin/');
    }

    #[Test]
    public function the_edit_shortcut_cannot_be_used_to_redirect_to_another_site(): void
    {
        $this->get('/edit?path=https://evil.example/')->assertRedirect('https://cms.test/wp-admin/admin.php?page=id5-site-editor&path=%2F');
        $this->get('/edit?path=//evil.example/')->assertRedirect('https://cms.test/wp-admin/admin.php?page=id5-site-editor&path=%2F');
    }
}
