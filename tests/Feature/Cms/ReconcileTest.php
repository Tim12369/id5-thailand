<?php

namespace Tests\Feature\Cms;

use App\Models\Article;
use App\Models\OrgChartMember;
use App\Models\OrgChartTier;
use App\Services\Cms\CmsSyncService;
use App\Services\Cms\MediaMirror;
use App\Services\Cms\Mappers\PersonMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The reconcile is the safety net behind the instant notifications, so it has
 * to repair every kind of drift — including content that was removed in
 * WordPress while a notification was lost.
 */
class ReconcileTest extends TestCase
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

    private function announcement(int $wpPostId, string $status = 'published'): array
    {
        return [
            'wp_post_id'   => $wpPostId,
            'post_type'    => 'id5_announcement',
            'article_type' => 'announcement',
            'title'        => 'ประกาศ '.$wpPostId,
            'slug'         => 'announcement-'.$wpPostId,
            'excerpt'      => '',
            'content'      => '<p>เนื้อหา</p>',
            'status'       => $status,
            'menu_order'   => 0,
            'published_at' => '2026-03-01T03:00:00+00:00',
            'image'        => null,
            'fields'       => [],
            'terms'        => [],
        ];
    }

    private function fakeCollection(array $items): void
    {
        Http::fake([
            'cms.test/wp-json/id5/v1/collection/id5_announcement' => Http::response([
                'post_type' => 'id5_announcement',
                'items'     => $items,
            ]),
        ]);
    }

    #[Test]
    public function content_deleted_in_wordpress_is_retired_even_if_the_notification_was_lost(): void
    {
        $this->fakeCollection([$this->announcement(1), $this->announcement(2)]);
        CmsSyncService::make()->syncType('id5_announcement');
        $this->assertSame(2, Article::count());

        // Post 2 is permanently deleted in WordPress; no webhook ever arrives.
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->fakeCollection([$this->announcement(1)]);
        CmsSyncService::make()->syncType('id5_announcement');

        $this->assertSame([1], Article::pluck('wp_post_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, Article::withTrashed()->count(), 'retired, not destroyed');
    }

    #[Test]
    public function a_trashed_item_reported_by_wordpress_is_hidden(): void
    {
        $this->fakeCollection([$this->announcement(1), $this->announcement(2, 'archived')]);
        CmsSyncService::make()->syncType('id5_announcement');

        $this->assertSame(1, Article::published()->public()->count());
    }

    #[Test]
    public function an_empty_answer_from_wordpress_never_wipes_a_whole_section(): void
    {
        $this->fakeCollection([$this->announcement(1), $this->announcement(2)]);
        CmsSyncService::make()->syncType('id5_announcement');

        // A misconfigured or freshly reinstalled WordPress answers with nothing.
        Http::swap(new \Illuminate\Http\Client\Factory());
        $this->fakeCollection([]);
        CmsSyncService::make()->syncType('id5_announcement');

        $this->assertSame(2, Article::published()->public()->count());
    }

    #[Test]
    public function an_empty_setting_in_wordpress_leaves_the_websites_default_in_place(): void
    {
        Http::fake([
            'cms.test/wp-json/id5/v1/settings' => Http::response(['settings' => [
                'general.site_name'       => '',
                'general.emergency_phone' => '',
                'home.hero_title_th'      => 'หัวข้อที่ตั้งไว้',
            ]]),
        ]);

        // A blank left behind by an earlier sync must be cleared too.
        \App\Models\Setting::set('general.site_name', '');

        CmsSyncService::make()->syncSettings();

        // The header and the emergency page fall back to their built-in values…
        $this->assertSame('ID5 @ Thailand 5.0', \App\Models\Setting::get('general.site_name', 'ID5 @ Thailand 5.0'));
        $this->assertSame('1178', \App\Models\Setting::get('general.emergency_phone', '1178'));
        // …while a value that was actually set is stored.
        $this->assertSame('หัวข้อที่ตั้งไว้', \App\Models\Setting::get('home.hero_title_th'));
    }

    #[Test]
    public function only_published_people_stand_on_the_organisation_chart(): void
    {
        $mapper = new PersonMapper(new MediaMirror(config('cms.media')));

        $person = fn (string $status) => [
            'wp_post_id' => 50,
            'title'      => 'พ.ต.อ. ทดสอบ ระบบ',
            'status'     => $status,
            'menu_order' => 1,
            'image'      => null,
            'fields'     => ['tier_label_th' => 'ผู้บังคับการ', 'tier_order' => 1],
            'terms'      => ['id5_org_section' => [[
                'term_id' => 9, 'name' => 'กองบังคับการ', 'slug' => 'division5',
                'fields'  => ['theme' => 'navy', 'sort_order' => 1, 'show_on_about_page' => true],
            ]]],
        ];

        $mapper->upsert($person('published'));
        $this->assertSame(1, OrgChartMember::count());

        // Moved to the trash (or back to draft) in WordPress.
        $mapper->upsert($person('archived'));
        $this->assertSame(0, OrgChartMember::count());
        $this->assertSame(0, OrgChartTier::count(), 'and the emptied row of the chart goes with them');

        // Restored and published again: back on the chart, nothing retyped.
        $mapper->upsert($person('published'));
        $this->assertSame('พ.ต.อ. ทดสอบ ระบบ', OrgChartMember::first()->name_th);
    }
}
