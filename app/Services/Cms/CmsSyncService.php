<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Faq;
use App\Models\ItaCard;
use App\Models\ItaSection;
use App\Models\MenuItem;
use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\OrgChartTier;
use App\Models\Setting;
use App\Services\Cms\Mappers\Mapper;
use App\Support\CmsText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Brings this application's content tables in line with WordPress.
 *
 * Two routes in: a webhook saying "post 12 of type id5_faq changed", and a full
 * reconcile run from the scheduler or by hand. Both end up here, and both are
 * idempotent — a payload is matched to its row by `wp_post_id`, so replaying a
 * notification or re-running a pull changes nothing.
 *
 * Nothing in this class is reachable from a visitor's request. The public site
 * reads the tables this writes, never WordPress itself, which is what keeps the
 * site serving normally while WordPress is down, slow or being upgraded.
 */
class CmsSyncService
{
    public function __construct(
        private readonly WordPressClient $client,
        private readonly MediaMirror $media,
    ) {}

    public static function make(): self
    {
        return new self(WordPressClient::fromConfig(), MediaMirror::fromConfig());
    }

    // ─── Single post ──────────────────────────────────────────────────────

    /**
     * Pull one post and write it into its table.
     *
     * @return bool Whether anything was written.
     */
    public function syncPost(string $postType, int $wpPostId): bool
    {
        $mapper = $this->mapperFor($postType);

        if (! $mapper) {
            return false;
        }

        $payload = $this->client->post($wpPostId);

        if ($payload === null) {
            // Gone from WordPress between the notification and this read.
            $mapper->delete($wpPostId);

            return true;
        }

        return $mapper->upsert($payload) !== null;
    }

    /**
     * Apply a permanent deletion in WordPress.
     */
    public function deletePost(string $postType, int $wpPostId): bool
    {
        $mapper = $this->mapperFor($postType);

        if (! $mapper) {
            return false;
        }

        $mapper->delete($wpPostId);

        return true;
    }

    // ─── Bulk ─────────────────────────────────────────────────────────────

    /**
     * Pull every post of one type.
     *
     * @return array{synced:int,skipped:int,failed:int}
     */
    public function syncType(string $postType): array
    {
        $mapper = $this->mapperFor($postType);

        if (! $mapper) {
            return ['synced' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $response = $this->client->collection($postType);
        $items    = $response['items'] ?? [];

        $result = ['synced' => 0, 'skipped' => 0, 'failed' => 0];

        $this->retireMissing($mapper, $postType, $items);

        foreach ($items as $payload) {
            try {
                if ($mapper->upsert($payload) !== null) {
                    $result['synced']++;
                } else {
                    $result['skipped']++;
                }
            } catch (Throwable $e) {
                $result['failed']++;

                Log::channel('cms')->error('Failed to sync one item.', [
                    'post_type'  => $postType,
                    'wp_post_id' => $payload['wp_post_id'] ?? null,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->recordRun('pull', $postType, $result['failed'] === 0 ? 'ok' : 'partial', json_encode($result));

        return $result;
    }

    /**
     * Pull everything: all content types, then the settings and menus.
     *
     * @param  string[] $types Restrict to these post types; empty means all.
     * @return array<string,array{synced:int,skipped:int,failed:int}>
     */
    public function syncAll(array $types = []): array
    {
        $mappers = config('cms.mappers');
        $wanted  = $types ?: array_keys($mappers);
        $summary = [];

        foreach ($wanted as $postType) {
            if (! isset($mappers[$postType])) {
                continue;
            }

            $summary[$postType] = $this->syncType($postType);
        }

        if (! $types) {
            $summary['_settings'] = ['synced' => $this->syncSettings(), 'skipped' => 0, 'failed' => 0];
            $summary['_menus']    = ['synced' => $this->syncMenus(), 'skipped' => 0, 'failed' => 0];
            $summary['_texts']    = ['synced' => $this->syncTexts(), 'skipped' => 0, 'failed' => 0];
            $summary['_terms']    = ['synced' => $this->syncTerms(), 'skipped' => 0, 'failed' => 0];
        }

        return $summary;
    }

    /**
     * Retire what predates WordPress: rows of WordPress-managed content that
     * no WordPress item owns.
     *
     * A database filled on its own — seed data on a demonstration site — has
     * its own news, FAQs, organisation chart and ITA topics. The first pull
     * adds WordPress's next to them and every one of those appears twice.
     * (Offices, forms, homepage cards and guide topics do not: they are found
     * by their own code or key, and the existing row is simply taken over.)
     *
     * Deliberately not part of an ordinary pull, which leaves rows it does not
     * own alone. It is asked for — `cms:pull --retire-unmanaged`, or
     * CMS_RETIRE_UNMANAGED for a host with no command line — and it only acts
     * on a type the pull just fetched completely: at least one item, and no
     * failures. News is moved to the trash and FAQs hidden, both recoverable;
     * people and ITA topics, which have no such state, are removed.
     *
     * @param  array<string,array{synced:int,skipped:int,failed:int}> $summary What syncAll() returned.
     * @return array<string,int> Rows retired, by kind.
     */
    public function retireUnmanaged(array $summary): array
    {
        $complete = fn (string $postType) => ($summary[$postType]['synced'] ?? 0) > 0 && ($summary[$postType]['failed'] ?? 1) === 0;
        $retired  = [];

        foreach (['post' => 'news', 'id5_announcement' => 'announcement', 'id5_alert' => 'alert', 'id5_knowledge' => 'kb_public'] as $postType => $type) {
            if ($complete($postType)) {
                $retired[$type] = Article::where('type', $type)->whereNull('wp_post_id')->get()->each->delete()->count();
            }
        }

        if ($complete('id5_faq')) {
            $retired['faq'] = Faq::whereNull('wp_post_id')->where('is_active', true)->update(['is_active' => false]);
        }

        if ($complete('id5_person')) {
            $retired['person'] = OrgChartMember::whereNull('wp_post_id')->delete();

            // Rows and units left with nobody in them, that WordPress does not know.
            OrgChartTier::whereNull('cms_key')->doesntHave('members')->delete();
            OrgChartSection::whereNull('wp_term_id')->doesntHave('tiers')->delete();
        }

        if ($complete('id5_ita_card')) {
            $retired['ita_topic'] = ItaCard::whereNull('wp_post_id')->delete();

            ItaSection::whereNull('wp_term_id')->doesntHave('cards')->delete();
        }

        $retired = array_filter($retired);

        if ($retired) {
            Log::channel('cms')->info('Retired content that predates WordPress.', $retired);
        }

        return $retired;
    }

    /**
     * Retire content that is live here but no longer exists in WordPress.
     *
     * This is what lets the reconcile repair a *deletion* whose notification
     * never arrived — without it, something permanently deleted in WordPress
     * while the site was deploying would stay on the public website forever.
     *
     * One guard: if WordPress returns nothing at all for a type that has live
     * content here, nothing is retired. An empty answer is far more likely to
     * mean a misconfigured or freshly reinstalled WordPress than an editor
     * deliberately deleting every item, and hiding a whole section of a
     * government website on that evidence would be the wrong failure mode.
     *
     * @return int Number of items retired.
     */
    private function retireMissing(Mapper $mapper, string $postType, array $items): int
    {
        $known = $mapper->knownPostIds($postType);

        if (! $known) {
            return 0;
        }

        if (! $items) {
            Log::channel('cms')->warning('WordPress returned no items for a type that has live content; nothing was retired.', [
                'post_type' => $postType,
                'live_here' => count($known),
            ]);

            return 0;
        }

        $present = array_map(fn (array $item) => (int) ($item['wp_post_id'] ?? 0), $items);
        $missing = array_diff(array_map('intval', $known), $present);

        foreach ($missing as $wpPostId) {
            $mapper->delete($wpPostId);

            Log::channel('cms')->info('Retired content that no longer exists in WordPress.', [
                'post_type'  => $postType,
                'wp_post_id' => $wpPostId,
            ]);
        }

        return count($missing);
    }

    // ─── Settings ─────────────────────────────────────────────────────────

    /**
     * Pull the Customizer values into the `settings` table the public layout
     * already reads.
     *
     * @return int Number of settings written.
     */
    public function syncSettings(): int
    {
        $response = $this->client->settings();
        $settings = $response['settings'] ?? [];
        $written  = 0;

        foreach ($settings as $key => $value) {
            // The hero image arrives as a WordPress URL and is mirrored locally,
            // because the public view renders it with asset('storage/'.$path).
            if ($key === 'site.hero_image') {
                $value = $value
                    ? (string) $this->media->mirror(['url' => $value], Setting::get($key))
                    : '';
            }

            // An empty value in WordPress means "nothing chosen": the website
            // should fall back to its own built-in wording. Storing the empty
            // string instead would *replace* that default with a blank — a
            // header with no site name, an emergency page with no number — so
            // the row is removed and the default shows through.
            if ((string) $value === '') {
                Setting::where('key', $key)->delete();
                Cache::forget("setting:{$key}");
            } else {
                Setting::set($key, (string) $value);
            }

            $written++;
        }

        $this->recordRun('pull', '_settings', 'ok', "{$written} settings");

        return $written;
    }

    // ─── Categories ───────────────────────────────────────────────────────

    /** Which `categories.type` each WordPress taxonomy feeds. */
    public const CATEGORY_TYPES = [
        'category'             => ['news'],
        'id5_content_category' => ['kb_public', 'faq', 'announcement', 'alert'],
    ];

    /**
     * Bring categories and sections in line with WordPress: their names, and
     * whether they still exist.
     *
     * Content sync already updates a category whenever an item carrying it is
     * pulled. That leaves out two things, both handled here:
     *
     *  - the empty ones — which the website still lists, in the knowledge-base
     *    sidebar for instance — so a rename in WordPress never reached them;
     *  - the deleted ones. A section removed in WordPress has, by definition,
     *    no item left to carry the news, so it stayed on the website for ever.
     *
     * Every WordPress category is matched to its row (by the stored link, else
     * by slug or Thai name) and written; every row linked to a category that
     * WordPress no longer has is removed.
     *
     * It never creates a category: one that exists only in WordPress and is
     * attached to nothing has no section of the website to appear in.
     *
     * @return int Number of rows written or removed.
     */
    public function syncTerms(): int
    {
        $terms = $this->client->terms()['terms'] ?? null;

        if (! is_array($terms)) {
            throw new \RuntimeException('WordPress returned an unexpected terms payload.');
        }

        $written = $this->syncItaSections($terms) + $this->syncOrgSections($terms) + $this->retireCategories($terms);

        foreach (self::CATEGORY_TYPES as $taxonomy => $types) {
            foreach ($terms[$taxonomy] ?? [] as $term) {
                if (in_array($term['slug'], ['uncategorized', 'uncategorised'], true)) {
                    continue;
                }

                foreach ($types as $type) {
                    $linked   = Category::where('type', $type)->where('wp_term_id', $term['term_id'])->first();
                    $category = $linked ?? Category::where('type', $type)
                        ->whereNull('wp_term_id')
                        ->where(fn ($q) => $q->where('slug', $term['slug'])->orWhere('name_th', $term['name']))
                        ->first();

                    if (! $category) {
                        continue;
                    }

                    $nameEn = $term['fields']['name_en'] ?? null;

                    $category->name_th = $term['name'];
                    // A category being adopted keeps its English name until
                    // WordPress has one of its own to replace it with.
                    $category->name_en = filled($nameEn) ? $nameEn : ($linked ? null : $category->name_en);
                    $category->wp_term_id = $term['term_id'];
                    $category->save();

                    $written++;
                }
            }
        }

        $this->recordRun('pull', '_terms', 'ok', "{$written} categories");

        return $written;
    }

    /**
     * The IDs WordPress currently has in one taxonomy — or null when the
     * answer cannot be trusted to mean "these are all of them".
     *
     * Removing rows is only safe against a complete list. A taxonomy missing
     * from the answer (an older plugin) or coming back empty while this side
     * still has linked rows (a rebuilt or misconfigured WordPress) is treated
     * as "unknown", and nothing is removed.
     *
     * @return int[]|null
     */
    private function termIds(array $terms, string $taxonomy, int $linkedHere): ?array
    {
        if (! isset($terms[$taxonomy]) || ! is_array($terms[$taxonomy])) {
            return null;
        }

        $ids = array_map(fn (array $term) => (int) $term['term_id'], $terms[$taxonomy]);

        if (! $ids && $linkedHere > 0) {
            Log::channel('cms')->warning('WordPress returned no terms for a taxonomy that has linked rows here; nothing was removed.', [
                'taxonomy'    => $taxonomy,
                'linked_here' => $linkedHere,
            ]);

            return null;
        }

        return $ids;
    }

    /**
     * ITA sections: rename the ones WordPress still has (also when no topic
     * is in them), and remove the ones it deleted together with their topics.
     *
     * A topic that lost its section this way still exists in WordPress; giving
     * it a section there brings it back.
     */
    private function syncItaSections(array $terms): int
    {
        $changed = 0;

        foreach ($terms['id5_ita_section'] ?? [] as $term) {
            $section = ItaSection::where('wp_term_id', $term['term_id'])->first();

            if (! $section) {
                continue; // appears with its first published topic
            }

            $section->fill([
                'section_number' => $term['fields']['section_number'] ?? '',
                'title_th'       => $term['name'],
                'title_en'       => $term['fields']['title_en'] ?? null,
                'description_th' => $term['fields']['description_th'] ?? null,
                'description_en' => $term['fields']['description_en'] ?? null,
                'sort_order'     => (int) ($term['fields']['sort_order'] ?? 0),
                'is_visible'     => (bool) ($term['fields']['is_visible'] ?? true),
            ])->save();

            $changed++;
        }

        $present = $this->termIds($terms, 'id5_ita_section', ItaSection::whereNotNull('wp_term_id')->count());

        if ($present === null) {
            return $changed;
        }

        foreach (ItaSection::whereNotNull('wp_term_id')->whereNotIn('wp_term_id', $present)->get() as $section) {
            ItaCard::where('section_id', $section->id)->delete();
            $section->delete();
            $changed++;

            Log::channel('cms')->info('Removed an ITA section that no longer exists in WordPress.', ['wp_term_id' => $section->wp_term_id, 'title' => $section->title_th]);
        }

        return $changed;
    }

    /**
     * Organisation-chart units: the same, for the people standing in them.
     */
    private function syncOrgSections(array $terms): int
    {
        $changed = 0;

        foreach ($terms['id5_org_section'] ?? [] as $term) {
            $section = OrgChartSection::where('wp_term_id', $term['term_id'])->first();

            if (! $section) {
                continue;
            }

            $section->fill([
                'name_th'            => $term['name'],
                'name_en'            => $term['fields']['name_en'] ?? null,
                'theme'              => $term['fields']['theme'] ?? 'navy',
                'sort_order'         => (int) ($term['fields']['sort_order'] ?? 0),
                'show_on_about_page' => (bool) ($term['fields']['show_on_about_page'] ?? true),
            ])->save();

            $changed++;
        }

        $present = $this->termIds($terms, 'id5_org_section', OrgChartSection::whereNotNull('wp_term_id')->count());

        if ($present === null) {
            return $changed;
        }

        foreach (OrgChartSection::whereNotNull('wp_term_id')->whereNotIn('wp_term_id', $present)->get() as $section) {
            $tiers = OrgChartTier::where('section_id', $section->id)->pluck('id');

            OrgChartMember::whereIn('tier_id', $tiers)->delete();
            OrgChartTier::whereIn('id', $tiers)->delete();
            $section->delete();
            $changed++;

            Log::channel('cms')->info('Removed an organisation-chart unit that no longer exists in WordPress.', ['wp_term_id' => $section->wp_term_id, 'name' => $section->name_th]);
        }

        return $changed;
    }

    /**
     * Article and FAQ categories deleted in WordPress. Whatever was filed
     * under one simply becomes uncategorised (the database clears the link).
     */
    private function retireCategories(array $terms): int
    {
        $removed = 0;

        foreach (self::CATEGORY_TYPES as $taxonomy => $types) {
            $linked  = Category::whereIn('type', $types)->whereNotNull('wp_term_id');
            $present = $this->termIds($terms, $taxonomy, (clone $linked)->count());

            if ($present === null) {
                continue;
            }

            foreach ((clone $linked)->whereNotIn('wp_term_id', $present)->get() as $category) {
                $category->delete();
                $removed++;

                Log::channel('cms')->info('Removed a category that no longer exists in WordPress.', ['wp_term_id' => $category->wp_term_id, 'name' => $category->name_th]);
            }
        }

        return $removed;
    }

    // ─── Wording ──────────────────────────────────────────────────────────

    /**
     * Pull the wording editors have changed — menu labels, headings, buttons,
     * the service guides — into `cms_texts`, which App\Support\CmsTranslator
     * layers over the language files.
     *
     * The table mirrors WordPress exactly: a line an editor put back to its
     * original wording has no row there, so it loses its row here and the
     * built-in text returns. Three things are refused on the way in:
     *
     *  - a line this version of the website does not have (nothing would ever
     *    print it);
     *  - wording identical to the built-in text (it is not a change);
     *  - markup beyond simple inline formatting, in the few lines printed as
     *    HTML — see CmsText::clean(). Every other line is printed escaped.
     *
     * One guard, as for content: a WordPress that has never had a line saved
     * in it and returns nothing is a fresh or rebuilt install, not an editor
     * resetting everything, so existing changes are kept until `cms:push
     * --texts` has put them back.
     *
     * @return int Number of changed lines now in force.
     */
    public function syncTexts(): int
    {
        $response = $this->client->texts();

        if (! isset($response['texts']) || ! is_array($response['texts'])) {
            throw new \RuntimeException('WordPress returned an unexpected texts payload.');
        }

        $catalogue = CmsText::catalogue();
        $rows      = [];

        foreach ($response['texts'] as $text) {
            $key    = (string) ($text['key'] ?? '');
            $locale = (string) ($text['locale'] ?? '');

            if (! isset($catalogue[$key]) || ! in_array($locale, CmsText::LOCALES, true)) {
                continue;
            }

            $value = CmsText::clean($key, (string) ($text['value'] ?? ''));

            if ($value === '' || $value === $catalogue[$key][$locale]) {
                continue;
            }

            $rows[$key.'|'.$locale] = [
                'key'        => $key,
                'locale'     => $locale,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! $rows && empty($response['initialised']) && DB::table('cms_texts')->exists()) {
            Log::channel('cms')->warning('WordPress holds no wording and has never had any saved; the changes stored here were kept.');
            $this->recordRun('pull', '_texts', 'partial', 'kept local wording: WordPress looks freshly installed');

            return DB::table('cms_texts')->count();
        }

        DB::transaction(function () use ($rows) {
            DB::table('cms_texts')->delete();

            foreach (array_chunk(array_values($rows), 200) as $chunk) {
                DB::table('cms_texts')->insert($chunk);
            }
        });

        CmsText::flush();

        $this->recordRun('pull', '_texts', 'ok', count($rows).' lines');

        return count($rows);
    }

    // ─── Menus ────────────────────────────────────────────────────────────

    /**
     * Pull the header and footer menus.
     *
     * Each location mirrors WordPress exactly, including "no menu": when an
     * administrator removes their menu, its items are removed here too and the
     * public layout falls back to its built-in navigation. That fallback is what
     * makes mirroring an empty menu safe — the site can never be left with no
     * navigation at all — and it gives administrators a way back to the
     * original menu, which simply ignoring an empty answer would not.
     *
     * (A WordPress outage does not reach this point: the request above throws
     * first, so nothing is cleared on a failed call.)
     *
     * @return int Number of menu items written.
     */
    public function syncMenus(): int
    {
        $response = $this->client->menus();

        if (! isset($response['menus']) || ! is_array($response['menus'])) {
            throw new \RuntimeException('WordPress returned an unexpected menus payload.');
        }

        $menus   = $response['menus'];
        $written = 0;

        foreach (['header', 'footer'] as $location) {
            $items = $menus[$location] ?? [];

            DB::transaction(function () use ($location, $items, &$written) {
                MenuItem::where('location', $location)->delete();

                $idMap = [];

                // Parents first, so a child always finds its parent's new ID.
                usort($items, fn ($a, $b) => ($a['wp_parent_id'] <=> $b['wp_parent_id']));

                foreach ($items as $item) {
                    $menuItem = MenuItem::create([
                        'location'   => $location,
                        'label_th'   => $item['label_th'],
                        'label_en'   => $item['label_en'] ?: null,
                        'url'        => $item['url'],
                        'parent_id'  => $idMap[$item['wp_parent_id']] ?? null,
                        'sort_order' => $item['sort_order'],
                        'is_visible' => true,
                    ]);

                    $idMap[$item['wp_item_id']] = $menuItem->id;
                    $written++;
                }
            });
        }

        $this->recordRun('pull', '_menus', 'ok', "{$written} items");

        return $written;
    }

    // ─── Plumbing ─────────────────────────────────────────────────────────

    /**
     * The mapper that owns a WordPress post type, or null if it is not ours.
     */
    public function mapperFor(string $postType): ?Mapper
    {
        $class = config("cms.mappers.{$postType}");

        if (! $class || ! class_exists($class)) {
            return null;
        }

        return new $class($this->media);
    }

    public function client(): WordPressClient
    {
        return $this->client;
    }

    /**
     * Record that a sync ran, so `cms:status` and the internal portal can show
     * whether content is current without anyone reading a log file.
     */
    private function recordRun(string $kind, string $scope, string $status, ?string $message = null): void
    {
        $key     = ['kind' => $kind, 'scope' => $scope];
        $values  = [
            'status'      => $status,
            'message'     => $message,
            'last_run_at' => now(),
            'updated_at'  => now(),
        ];

        $updated = DB::table('cms_sync_state')->where($key)->update($values);

        if ($updated === 0) {
            DB::table('cms_sync_state')->insert($key + $values + ['created_at' => now()]);
        }
    }
}
