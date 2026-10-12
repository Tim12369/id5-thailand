<?php

namespace Tests\Feature\Cms;

use App\Models\Category;
use App\Services\Cms\CmsSyncService;
use App\Support\Assessment\StatusDefinition;
use App\Support\CmsText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The fixed wording of the website — menu labels, headings, buttons, the
 * assessment questions — can be changed from WordPress.
 *
 * What matters: a change shows where the line is printed and nowhere else;
 * removing it brings the built-in wording back; nothing an editor types can
 * become markup or break a script; and visitors never receive the invisible
 * tags that tell the edit-mode page which line is which.
 */
class EditableTextTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'text-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.enabled'        => true,
            'cms.base_url'       => 'https://cms.test',
            'cms.auth.username'  => 'sync',
            'cms.auth.password'  => 'app-password',
            'cms.retries'        => 0,
            'cms.webhook.secret' => self::SECRET,
        ]);
    }

    private function change(string $key, string $locale, string $value): void
    {
        DB::table('cms_texts')->updateOrInsert(['key' => $key, 'locale' => $locale], ['value' => $value]);
        CmsText::flush();
    }

    private function fakeWordPress(array $texts, bool $initialised = true): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'cms.test/wp-json/id5/v1/texts' => Http::response(['texts' => $texts, 'initialised' => $initialised]),
        ]);
    }

    private function editToken(): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['n' => 'Editor', 'e' => time() + 3600])), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', 'id5-edit-mode|'.$payload, self::SECRET);
    }

    // ─── A change shows, and goes away again ──────────────────────────────

    #[Test]
    public function a_changed_line_replaces_the_built_in_wording_in_its_own_language_only(): void
    {
        $this->change('nav.about', 'th', 'เกี่ยวกับหน่วยงานของเรา');

        $this->get('/?lang=th')->assertOk()->assertSee('เกี่ยวกับหน่วยงานของเรา');
        $this->get('/?lang=en')->assertOk()->assertDontSee('เกี่ยวกับหน่วยงานของเรา')->assertSee(__('nav.about', [], 'en'));
    }

    #[Test]
    public function removing_the_change_brings_the_built_in_wording_back(): void
    {
        $original = $this->get('/?lang=th')->getContent();

        $this->change('nav.about', 'th', 'ชั่วคราว-ทดสอบ');
        $this->assertStringContainsString('ชั่วคราว-ทดสอบ', $this->get('/?lang=th')->getContent());

        DB::table('cms_texts')->delete();
        CmsText::flush();

        $this->assertSame($this->stable($original), $this->stable($this->get('/?lang=th')->getContent()));
    }

    #[Test]
    public function placeholders_keep_working_in_a_changed_line(): void
    {
        $this->change('search.found', 'en', 'We found :count things for ":query"');

        $this->assertSame('We found 7 things for "visa"', __('search.found', ['count' => 7, 'query' => 'visa'], 'en'));
    }

    #[Test]
    public function an_assessment_question_and_a_status_name_can_be_changed(): void
    {
        $this->change('assessment_q.who_are_you.title', 'en', 'Tell us who you are');
        $this->change('assessment_status.tourist', 'th', 'ผู้มาเยือน');

        $this->get('/assessment?lang=en')->assertOk()->assertSee('Tell us who you are');

        app()->setLocale('th');
        $this->assertSame('ผู้มาเยือน', StatusDefinition::fromArray('tourist', config('assessment.statuses.tourist'))->localizedLabel());
    }

    #[Test]
    public function with_no_changes_and_no_table_the_website_still_renders(): void
    {
        DB::statement('DROP TABLE cms_texts');
        CmsText::flush();

        $this->get('/?lang=th')->assertOk()->assertSee(__('nav.about', [], 'th'));
    }

    // ─── Nothing typed can become markup or break a script ────────────────

    #[Test]
    public function markup_typed_into_an_ordinary_line_is_shown_as_text(): void
    {
        $this->change('nav.about', 'en', 'About <script>alert(1)</script> "us" & <b>co</b>');

        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('About &lt;script&gt;alert(1)&lt;/script&gt; &quot;us&quot; &amp; &lt;b&gt;co&lt;/b&gt;', false)
            ->assertDontSee('About <script>alert(1)', false);
    }

    #[Test]
    public function a_line_handed_to_javascript_stays_a_valid_string_whatever_is_typed(): void
    {
        $this->change('layout.open_menu', 'en', "Open the site's \"menu\" </script><b>");

        $html = $this->get('/?lang=en')->assertOk()->getContent();

        // Quotes and angle brackets are written as \u escapes, so the string
        // cannot end early and cannot close the attribute or a script block.
        $this->assertStringContainsString("'Open the site\\u0027s \\u0022menu\\u0022 \\u003C/script\\u003E\\u003Cb\\u003E'", $html);
        $this->assertStringNotContainsString("Open the site's \"menu\"", $html);
    }

    #[Test]
    public function the_javascript_string_helper_leaves_ordinary_wording_exactly_as_it_was(): void
    {
        $this->assertSame("'เปิดเมนู'", CmsText::js('เปิดเมนู'));
        $this->assertSame("'a\\\\b\\nc'", CmsText::js("a\\b\r\nc"));
    }

    #[Test]
    public function a_line_printed_as_html_keeps_simple_formatting_and_loses_everything_else(): void
    {
        $clean = CmsText::clean(
            'visa.overstay_fine_html',
            'Fine of <strong class="x" onclick="steal()">฿500</strong> <script>alert(1)</script>'
            .'<img src=x onerror=alert(1)><a href="javascript:alert(1)">bad</a> <a href="/contact" target="_blank">ok</a><iframe src="//evil"></iframe>'
        );

        $this->assertStringContainsString('<strong class="x">฿500</strong>', $clean);
        $this->assertStringContainsString('<a href="/contact" target="_blank" rel="noopener noreferrer">ok</a>', $clean);

        foreach (['<script', 'onclick', 'onerror', '<img', 'javascript:', '<iframe'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $clean);
        }
    }

    #[Test]
    public function an_ordinary_line_is_stored_as_typed_apart_from_invisible_characters(): void
    {
        $this->assertSame("> 5 years & <more>\nline two", CmsText::clean('overstay.period_vvlong', "  > 5 years & <more>\r\nline two\u{2063}\x07 "));
    }

    // ─── Pulling the changes from WordPress ───────────────────────────────

    #[Test]
    public function a_pull_mirrors_wordpress_exactly(): void
    {
        $this->fakeWordPress([
            ['key' => 'nav.about', 'locale' => 'th', 'value' => 'เกี่ยวกับเรา (ใหม่)'],
            ['key' => 'nav.about', 'locale' => 'en', 'value' => 'About (new)'],
            ['key' => 'nav.contact', 'locale' => 'en', 'value' => 'Get in touch'],
        ]);

        $this->assertSame(3, CmsSyncService::make()->syncTexts());
        $this->assertSame('About (new)', __('nav.about', [], 'en'));

        // The editor puts one line back and changes another.
        $this->fakeWordPress([
            ['key' => 'nav.about', 'locale' => 'en', 'value' => 'About (newer)'],
        ]);

        $this->assertSame(1, CmsSyncService::make()->syncTexts());
        $this->assertSame('About (newer)', __('nav.about', [], 'en'));
        $this->assertSame(1, DB::table('cms_texts')->count());
        $this->assertNotSame('Get in touch', __('nav.contact', [], 'en'));
    }

    #[Test]
    public function a_pull_refuses_lines_that_do_not_exist_or_are_not_a_change(): void
    {
        $this->fakeWordPress([
            ['key' => 'nav.no_such_line', 'locale' => 'en', 'value' => 'x'],
            ['key' => 'nav.about', 'locale' => 'zz', 'value' => 'x'],
            ['key' => 'nav.about', 'locale' => 'en', 'value' => __('nav.about', [], 'en')],
            ['key' => 'home.hero_title', 'locale' => 'en', 'value' => 'has its own form'],
            ['key' => 'nav.about', 'locale' => 'th', 'value' => '   '],
            ['key' => 'visa.overstay_fine_html', 'locale' => 'en', 'value' => 'Fine <strong>x</strong><script>alert(1)</script>'],
        ]);

        $this->assertSame(1, CmsSyncService::make()->syncTexts());
        $this->assertSame('Fine <strong>x</strong>alert(1)', DB::table('cms_texts')->value('value'));
    }

    #[Test]
    public function when_every_change_is_undone_in_wordpress_the_website_goes_back_to_its_own_wording(): void
    {
        $this->change('nav.about', 'en', 'Temporary');
        $this->fakeWordPress([], initialised: true);

        $this->assertSame(0, CmsSyncService::make()->syncTexts());
        $this->assertSame(0, DB::table('cms_texts')->count());
    }

    #[Test]
    public function a_freshly_installed_wordpress_does_not_wipe_the_changes_held_here(): void
    {
        $this->change('nav.about', 'en', 'Keep me');
        $this->fakeWordPress([], initialised: false);

        CmsSyncService::make()->syncTexts();

        $this->assertSame('Keep me', DB::table('cms_texts')->value('value'));
    }

    #[Test]
    public function wordpress_announcing_a_change_makes_the_website_pull_it(): void
    {
        config(['cms.sync.mode' => 'queue', 'queue.default' => 'sync']);
        $this->fakeWordPress([['key' => 'nav.contact', 'locale' => 'en', 'value' => 'Talk to us']]);

        $body      = json_encode(['event' => 'texts.updated', 'key' => 'nav.contact']);
        $timestamp = (string) time();

        $this->call('POST', '/cms/webhook', [], [], [], [
            'CONTENT_TYPE'         => 'application/json',
            'HTTP_X_ID5_TIMESTAMP' => $timestamp,
            'HTTP_X_ID5_SIGNATURE' => hash_hmac('sha256', $timestamp."\n".$body, self::SECRET),
        ], $body)->assertSuccessful();

        $this->assertSame('Talk to us', __('nav.contact', [], 'en'));
    }

    // ─── The list WordPress reads ─────────────────────────────────────────

    #[Test]
    public function the_catalogue_is_only_given_to_a_signed_request(): void
    {
        $this->getJson('/cms/texts')->assertStatus(401);

        $timestamp = (string) time();

        $this->getJson('/cms/texts', [
            'X-ID5-Timestamp' => $timestamp,
            'X-ID5-Signature' => hash_hmac('sha256', $timestamp."\n", 'the wrong secret'),
        ])->assertStatus(401);
    }

    #[Test]
    public function the_catalogue_lists_the_wording_with_its_built_in_text(): void
    {
        $this->change('nav.about', 'en', 'A change must not leak into the built-in text');

        // Signed exactly as the WordPress plugin signs it: a GET has no body.
        $timestamp = (string) time();
        $data = $this->get('/cms/texts', [
            'Accept'          => 'application/json',
            'X-ID5-Timestamp' => $timestamp,
            'X-ID5-Signature' => hash_hmac('sha256', $timestamp."\n", self::SECRET),
        ])->assertOk()->json();

        $lines = $data['lines'];

        $this->assertSame(trans('nav.home', [], 'th'), $lines['nav.home']['th']);
        $this->assertNotSame('A change must not leak into the built-in text', $lines['nav.about']['en']);
        $this->assertSame('เมนูนำทาง', $data['groups']['nav']);

        // The assessment wizard's wording, which lives in config, is included.
        $this->assertArrayHasKey('assessment_q.who_are_you.title', $lines);
        $this->assertArrayHasKey('assessment_status.undetermined', $lines);
        $this->assertTrue($lines['visa.overstay_fine_html']['html']);
        $this->assertFalse($lines['nav.home']['html']);

        // Not offered: the banner (it has its own form) and leftover lines.
        $this->assertArrayNotHasKey('home.hero_title', $lines);
        $this->assertArrayNotHasKey('messages.home.hero_title', $lines);

        foreach (config('cms_texts.unused') as $unused) {
            $this->assertArrayNotHasKey($unused, $lines);
        }

        // Every line has wording in both languages to start from.
        foreach ($lines as $key => $line) {
            $this->assertIsString($line['th'], "$key has no Thai wording");
            $this->assertIsString($line['en'], "$key has no English wording");
        }
    }

    // ─── Edit mode: tags for the editor, never for a visitor ──────────────

    #[Test]
    public function a_visitor_never_receives_the_invisible_tags(): void
    {
        foreach (['/', '/services/visa', '/assessment', '/faq', '/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringNotContainsString("\u{2063}", $html, "$path carries tag characters");
            $this->assertStringNotContainsString('data-cms-text', $html);
        }
    }

    #[Test]
    public function in_edit_mode_each_line_is_tagged_and_listed_for_the_editor(): void
    {
        $html = $this->get('/?lang=th&id5_edit='.$this->editToken())->assertOk()->getContent();

        $this->assertStringContainsString(__('nav.about', [], 'th')."\u{2063}", $html);
        $this->assertMatchesRegularExpression('/"texts":\[.*"nav\.about"/', $html);

        // The banner's lines are edited in their own form, not as loose text.
        $this->assertDoesNotMatchRegularExpression('/"texts":\[[^\]]*"home\.hero_title"/', $html);
    }

    #[Test]
    public function tags_are_not_added_to_answers_meant_for_scripts(): void
    {
        $this->get('/?id5_edit='.$this->editToken())->assertOk();   // edit mode is now remembered

        $json = $this->getJson('/ai/suggested/tourist')->assertOk()->getContent();

        $this->assertStringNotContainsString('\u2063', $json);
        $this->assertStringNotContainsString("\u{2063}", $json);
    }

    #[Test]
    public function the_ai_assistant_s_starter_questions_can_be_changed(): void
    {
        $this->change('ai.suggestions_tourist.0', 'en', 'Do I need a visa at all?');

        $questions = $this->getJson('/ai/suggested/tourist?lang=en')->assertOk()->json('questions');

        $this->assertSame('Do I need a visa at all?', $questions[0]);
        $this->assertCount(3, $questions);

        // An unknown persona still gets the tourist's questions.
        $this->assertSame($questions, $this->getJson('/ai/suggested/nobody?lang=en')->json('questions'));
    }

    // ─── Categories nothing uses yet ──────────────────────────────────────

    #[Test]
    public function a_category_no_article_uses_is_still_renamed_from_wordpress(): void
    {
        $empty = Category::create(['type' => 'kb_public', 'slug' => 'overstay', 'name_th' => 'อยู่เกินกำหนด', 'name_en' => 'Overstay', 'is_active' => true]);

        Http::fake([
            'cms.test/wp-json/id5/v1/terms' => Http::response(['terms' => [
                'id5_content_category' => [
                    ['term_id' => 108, 'taxonomy' => 'id5_content_category', 'name' => 'อยู่เกินกำหนด', 'slug' => 'overstay', 'fields' => ['name_en' => '']],
                    ['term_id' => 500, 'taxonomy' => 'id5_content_category', 'name' => 'ไม่มีในเว็บไซต์', 'slug' => 'nowhere', 'fields' => []],
                ],
            ]]),
        ]);

        CmsSyncService::make()->syncTerms();

        $empty->refresh();
        $this->assertSame(108, (int) $empty->wp_term_id);
        $this->assertSame('Overstay', $empty->name_en, 'adopting a category must not blank its English name');
        $this->assertSame(0, Category::where('slug', 'nowhere')->count(), 'a category attached to nothing is not created');

        // Now linked: a rename in WordPress reaches it.
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'cms.test/wp-json/id5/v1/terms' => Http::response(['terms' => [
                'id5_content_category' => [
                    ['term_id' => 108, 'taxonomy' => 'id5_content_category', 'name' => 'พำนักเกินกำหนด', 'slug' => 'overstay', 'fields' => ['name_en' => 'Overstaying']],
                ],
            ]]),
        ]);

        CmsSyncService::make()->syncTerms();

        $empty->refresh();
        $this->assertSame('พำนักเกินกำหนด', $empty->name_th);
        $this->assertSame('Overstaying', $empty->name_en);
    }

    // ─── Pencils on things that are not wording ───────────────────────────

    #[Test]
    public function a_menu_built_in_wordpress_gets_a_pencil_and_the_built_in_one_does_not(): void
    {
        $edit = '/?lang=th&id5_edit='.$this->editToken();

        // Built-in navigation: each label is editable wording, no menu pencil.
        $this->get($edit)->assertOk()->assertDontSee('data-cms-edit="menus"', false);

        \App\Models\MenuItem::create(['location' => 'header', 'label_th' => 'เมนูจากเวิร์ดเพรส', 'url' => '/faq', 'sort_order' => 1, 'is_visible' => true]);

        $this->get($edit)->assertOk()->assertSee('data-cms-edit="menus"', false)->assertSee('เมนูจากเวิร์ดเพรส');
        $this->get('/edit/exit');
        $this->get('/?lang=th')->assertOk()->assertSee('เมนูจากเวิร์ดเพรส')->assertDontSee('data-cms-edit', false);
    }

    #[Test]
    public function a_category_linked_to_wordpress_gets_a_pencil_that_opens_its_term(): void
    {
        $category = Category::create(['type' => 'kb_public', 'slug' => 'visa', 'name_th' => 'วีซ่า', 'name_en' => 'Visa', 'is_active' => true]);

        $this->assertSame('', \App\Support\CmsEdit::marker($category), 'no pencil outside edit mode');

        $this->get('/knowledge?id5_edit='.$this->editToken())->assertOk()->assertDontSee('data-cms-edit="term:', false);

        $category->forceFill(['wp_term_id' => 87])->save();

        $this->get('/knowledge')->assertOk()->assertSee('data-cms-edit="term:87"', false);
    }

    /** Strip the only things that differ between two requests for one page. */
    private function stable(string $html): string
    {
        return preg_replace('/[A-Za-z0-9]{40}(?=["\'])/', 'T', $html);
    }
}
