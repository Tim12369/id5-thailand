<?php

namespace App\Services\Cms\Mappers;

use App\Models\ItaCard;
use App\Models\ItaSection;
use App\Support\ItaIcon;
use Illuminate\Database\Eloquent\Model;

/**
 * Integrity & Transparency Assessment items.
 *
 * The section a card belongs to is a WordPress taxonomy, which gives editors
 * the familiar "pick a category" interaction and keeps section ordering and
 * numbering with the section rather than repeated on every card.
 */
class ItaCardMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        $sectionTerm = $this->term($payload, 'id5_ita_section');

        if (! $sectionTerm) {
            // Its section was deleted in WordPress. With nowhere on the page
            // to stand it leaves the website; choosing a section for it in
            // WordPress brings it back.
            $this->delete($payload['wp_post_id']);

            return null;
        }

        $section = $this->section($sectionTerm);

        $card = ItaCard::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        $card->fill([
            'section_id'  => $section->id,
            'card_number' => $this->field($payload, 'card_number', ''),
            'title_th'    => $payload['title'] ?: '',
            'title_en'    => $this->field($payload, 'title_en'),
            // Plain text on purpose: the public ITA page prints this escaped,
            // so HTML here would appear to visitors as literal tags.
            'content_th'  => $this->plainText($this->field($payload, 'content_th', '')),
            'content_en'  => $this->plainText($this->field($payload, 'content_en')),
            // Only a name the icon font really has is kept. Anything else —
            // a typo, or nothing — leaves it empty, and the page then picks an
            // icon to suit the topic.
            'icon'        => ItaIcon::normalise($this->field($payload, 'icon')),
            'color'       => $this->cssColor($this->field($payload, 'color')),
            'sort_order'  => $payload['menu_order'],
            'is_visible'  => $this->bool($payload, 'is_visible', true) && $this->isPublished($payload),
        ]);

        // An uploaded picture replaces the icon; removing it in WordPress
        // removes it here too.
        $card->icon_image = $this->media->mirror($this->attachment($payload, 'icon_image'), $card->icon_image);

        $card->wp_post_id    = $payload['wp_post_id'];
        $card->cms_synced_at = now();
        $card->save();

        return $card;
    }

    public function knownPostIds(string $postType): array
    {
        return ItaCard::whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    /**
     * A colour the page can put straight into a style: "#4f46e5" or a plain
     * colour name. Anything else is dropped and the page uses its default.
     */
    private function cssColor(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return preg_match('/^(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z]{3,20})$/', $value) ? $value : null;
    }

    public function delete(int $wpPostId): void
    {
        ItaCard::where('wp_post_id', $wpPostId)->delete();
    }

    /**
     * Find the section this card belongs to, adopting the pre-WordPress row
     * where one already describes the same section.
     *
     * Without the adoption step the first sync would build a parallel set of
     * sections beside the originals, and the ITA page would show every heading
     * twice — once with its cards and once empty.
     */
    private function section(array $term): ItaSection
    {
        $section = ItaSection::where('wp_term_id', $term['term_id'])->first();

        if (! $section) {
            $section = ItaSection::whereNull('wp_term_id')
                ->where(function ($q) use ($term) {
                    $q->where('title_th', $term['name']);

                    if (! empty($term['fields']['section_number'])) {
                        $q->orWhere('section_number', $term['fields']['section_number']);
                    }
                })
                ->first() ?? new ItaSection();
        }

        $section->fill([
            'section_number' => $term['fields']['section_number'] ?? '',
            'title_th'       => $term['name'],
            'title_en'       => $term['fields']['title_en'] ?? null,
            'description_th' => $term['fields']['description_th'] ?? null,
            'description_en' => $term['fields']['description_en'] ?? null,
            'sort_order'     => (int) ($term['fields']['sort_order'] ?? 0),
            'is_visible'     => (bool) ($term['fields']['is_visible'] ?? true),
        ]);

        $section->wp_term_id = $term['term_id'];
        $section->save();

        return $section;
    }
}
