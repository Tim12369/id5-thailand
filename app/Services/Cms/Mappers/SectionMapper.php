<?php

namespace App\Services\Cms\Mappers;

use App\Models\PageSection;
use Illuminate\Database\Eloquent\Model;

/**
 * Content sections and pages built from blocks in WordPress.
 *
 * WordPress sends the finished HTML of the blocks; the pictures and PDF
 * documents in it are copied to this website on the way in (Mapper::html()),
 * so a visitor's browser never has to reach WordPress for them.
 */
class SectionMapper extends Mapper
{
    /** WordPress post type => `page_sections.kind`. */
    private const KINDS = [
        'id5_section' => PageSection::SECTION,
        'id5_page'    => PageSection::PAGE,
    ];

    private const POSITIONS = ['bottom', 'after_hero', 'after_services', 'after_news'];

    private const BACKGROUNDS = ['none', 'white', 'light', 'brand'];

    public function knownPostIds(string $postType): array
    {
        $kind = self::KINDS[$postType] ?? null;

        return $kind ? PageSection::where('kind', $kind)->pluck('wp_post_id')->all() : [];
    }

    public function upsert(array $payload): ?Model
    {
        $kind = self::KINDS[$payload['post_type'] ?? ''] ?? null;

        if (! $kind) {
            return null;
        }

        $row = PageSection::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        $page = $kind === PageSection::SECTION ? $this->pagePath($this->field($payload, 'page', '/')) : null;

        $row->fill([
            'kind'         => $kind,
            'page'         => $page,
            'position'     => in_array($this->field($payload, 'position'), self::POSITIONS, true) ? $this->field($payload, 'position') : 'bottom',
            'slug'         => $kind === PageSection::PAGE ? $this->cleanSlug($payload['slug'] ?? null) : null,
            'title_th'     => $payload['title'] ?: null,
            'title_en'     => $this->field($payload, 'title_en'),
            'excerpt'      => $this->plainText($payload['excerpt'] ?? null) ?: null,
            'body_html'    => (string) $this->html($payload, $payload['content'] ?? ''),
            'language'     => in_array($this->field($payload, 'language'), ['both', 'th', 'en'], true) ? $this->field($payload, 'language') : 'both',
            'background'   => in_array($this->field($payload, 'background'), self::BACKGROUNDS, true) ? $this->field($payload, 'background') : 'none',
            'sort_order'   => (int) ($payload['menu_order'] ?? 0),
            'is_published' => $this->isPublished($payload),
        ]);

        $row->wp_post_id    = $payload['wp_post_id'];
        $row->cms_synced_at = now();
        $row->save();

        return $row;
    }

    public function delete(int $wpPostId): void
    {
        PageSection::where('wp_post_id', $wpPostId)->delete();
    }

    /** "/about", never "about/" or a full address. */
    private function pagePath(mixed $value): string
    {
        $path = '/'.trim((string) parse_url((string) $value, PHP_URL_PATH), '/');

        return preg_match('#^/[a-z0-9/-]*$#', $path) ? $path : '/';
    }
}
