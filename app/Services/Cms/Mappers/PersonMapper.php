<?php

namespace App\Services\Cms\Mappers;

use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\OrgChartTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Organisation chart people.
 *
 * The chart is three levels deep in the database — section, tier (a row of the
 * chart), then member — but asking an editor to maintain three linked lists in
 * WordPress would be worse than the custom screen it replaces. So in WordPress
 * a person simply belongs to a Section (a taxonomy they already understand) and
 * types the name of the row they sit in. This mapper rebuilds the tier rows
 * from those labels, keyed on section + label so that everyone who typed the
 * same row name lands in the same row.
 */
class PersonMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        // The chart has no "hidden" state — everyone in the table is drawn — so
        // a person who is a draft or in the trash in WordPress must not be in
        // the table at all. Their WordPress entry keeps every detail, and
        // publishing it again puts them straight back.
        if (! $this->isPublished($payload)) {
            $this->delete($payload['wp_post_id']);

            return null;
        }

        $sectionTerm = $this->term($payload, 'id5_org_section');

        if (! $sectionTerm) {
            // Without a section there is no chart to place this person in —
            // including when the unit they stood in was deleted in WordPress.
            $this->delete($payload['wp_post_id']);

            return null;
        }

        $section = $this->section($sectionTerm);
        $tier    = $this->tier($section, $payload);

        $member = OrgChartMember::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        $member->fill([
            'tier_id'     => $tier->id,
            'name_th'     => $payload['title'] ?: '',
            'name_en'     => $this->field($payload, 'name_en'),
            'rank_th'     => $this->field($payload, 'rank_th'),
            'position_th' => $this->field($payload, 'position_th'),
            'sort_order'  => $payload['menu_order'],
        ]);

        $member->photo_path = $this->media->mirror($payload['image'] ?? null, $member->photo_path);

        $member->wp_post_id    = $payload['wp_post_id'];
        $member->cms_synced_at = now();
        $member->save();

        $this->pruneEmptyTiers();

        return $member;
    }

    public function knownPostIds(string $postType): array
    {
        return OrgChartMember::whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    /**
     * A person removed in WordPress leaves the chart. Unlike articles there is
     * no public URL or counter to preserve, and a stale face on a leadership
     * chart is actively wrong, so the row goes.
     */
    public function delete(int $wpPostId): void
    {
        OrgChartMember::where('wp_post_id', $wpPostId)->delete();

        $this->pruneEmptyTiers();
    }

    private function section(array $term): OrgChartSection
    {
        $section = OrgChartSection::firstOrNew(['slug' => $term['slug']]);

        $section->fill([
            'name_th'            => $term['name'],
            'name_en'            => $term['fields']['name_en'] ?? null,
            'theme'              => $term['fields']['theme'] ?? 'navy',
            'sort_order'         => (int) ($term['fields']['sort_order'] ?? 0),
            'show_on_about_page' => (bool) ($term['fields']['show_on_about_page'] ?? true),
        ]);

        $section->wp_term_id = $term['term_id'];
        $section->save();

        return $section;
    }

    /**
     * Find or create the chart row this person belongs to. `cms_key` keeps the
     * identity stable even if the label is later re-typed with different
     * whitespace.
     */
    private function tier(OrgChartSection $section, array $payload): OrgChartTier
    {
        $label = trim((string) ($this->field($payload, 'tier_label_th') ?? ''));

        if ($label === '') {
            $label = 'อื่น ๆ';
        }

        $key = $section->id.':'.Str::slug($label, '-', null);

        $tier = OrgChartTier::firstOrNew(['cms_key' => $key]);

        $tier->fill([
            'section_id' => $section->id,
            'label_th'   => $label,
            'label_en'   => $this->field($payload, 'tier_label_en'),
            'sort_order' => $this->int($payload, 'tier_order', 0),
        ]);

        $tier->cms_key = $key;
        $tier->save();

        return $tier;
    }

    /**
     * A tier is only a container for the people standing in it, so one with
     * nobody in it has nothing to draw and would render as an empty band across
     * the chart.
     *
     * This deliberately covers tiers without a `cms_key` as well. At cut-over
     * every member moves from the tier the old admin portal created to the one
     * built from their tier label, and leaving the emptied originals behind
     * would double the number of rows in every chart.
     */
    private function pruneEmptyTiers(): void
    {
        OrgChartTier::whereDoesntHave('members')->delete();
    }
}
