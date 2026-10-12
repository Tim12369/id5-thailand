<?php

namespace App\Services\Cms\Mappers;

use App\Models\Category;
use App\Services\Cms\MediaMirror;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Base for the classes that turn one WordPress payload into one database row.
 *
 * A mapper writes only the columns it declares. Everything else — view counts,
 * download counts, Command Center operational state, foreign keys owned by
 * other subsystems — is left exactly as it is, which is what lets WordPress own
 * the editorial side of a table without owning the whole table.
 */
abstract class Mapper
{
    public function __construct(
        protected readonly MediaMirror $media,
    ) {}

    /**
     * Create or update the row this payload describes.
     *
     * @return Model|null The affected row, or null when the payload was skipped.
     */
    abstract public function upsert(array $payload): ?Model;

    /**
     * Handle a post that was permanently deleted in WordPress.
     *
     * The default is to do nothing: unpublishing is expressed as a status
     * change, and silently deleting public content because of one webhook would
     * be the wrong default for a government site. Mappers whose content is
     * genuinely disposable override this.
     */
    public function delete(int $wpPostId): void
    {
        //
    }

    /**
     * The WordPress post IDs this mapper currently has live on the website for
     * one post type.
     *
     * The reconcile compares this with what WordPress actually returned: an ID
     * that is live here but gone there was deleted in WordPress while the
     * instant notification was missed, and is retired through delete().
     * Mappers whose content is never removed return nothing.
     *
     * @return int[]
     */
    public function knownPostIds(string $postType): array
    {
        return [];
    }

    // ─── Payload helpers ──────────────────────────────────────────────────

    /** A schema field value, or null. */
    protected function field(array $payload, string $key, mixed $default = null): mixed
    {
        $value = $payload['fields'][$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    protected function bool(array $payload, string $key, bool $default = false): bool
    {
        $value = $payload['fields'][$key] ?? null;

        return $value === null ? $default : (bool) $value;
    }

    protected function int(array $payload, string $key, ?int $default = null): ?int
    {
        $value = $this->field($payload, $key);

        return $value === null ? $default : (int) $value;
    }

    /**
     * Text from WordPress's editor, with the pictures placed in it copied to
     * this website (see MediaMirror::mirrorHtml).
     */
    protected function html(array $payload, ?string $html): ?string
    {
        return $this->media->mirrorHtml($html, [$payload['uploads_url'] ?? null]);
    }

    protected function attachment(array $payload, string $key): ?array
    {
        $value = $payload['fields'][$key] ?? null;

        return is_array($value) ? $value : null;
    }

    /** Rows of a repeater field. */
    protected function rows(array $payload, string $key): array
    {
        $value = $payload['fields'][$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /** The first term attached in a taxonomy, or null. */
    protected function term(array $payload, string $taxonomy): ?array
    {
        return $payload['terms'][$taxonomy][0] ?? null;
    }

    /** All terms attached in a taxonomy. */
    protected function terms(array $payload, string $taxonomy): array
    {
        return $payload['terms'][$taxonomy] ?? [];
    }

    /**
     * Whether this payload is allowed to write to a row that is found by a key
     * (a card's key, a form's code, a topic) rather than by its post ID.
     *
     * Such a row has one owner: the WordPress item whose ID is stored on it. A
     * *different* item carrying the same key is a duplicate. WordPress refuses
     * to publish one, keeping it as a draft — but a draft is still synced, and
     * without this rule that draft (or a trashed copy) would overwrite the real
     * item's content on the live website while its author was told it had not
     * been published at all.
     *
     * So: the owner may always write. Anyone else may write only if they are
     * actually published, which WordPress permits only once the previous owner
     * is gone — a legitimate replacement.
     */
    protected function mayWrite(mixed $ownerWpPostId, array $payload): bool
    {
        if (blank($ownerWpPostId) || (int) $ownerWpPostId === (int) $payload['wp_post_id']) {
            return true;
        }

        if ($this->isPublished($payload)) {
            return true;
        }

        Log::channel('cms')->warning('Ignored an unpublished duplicate; the existing item keeps its content.', [
            'post_type'     => $payload['post_type'] ?? null,
            'duplicate'     => $payload['wp_post_id'],
            'current_owner' => (int) $ownerWpPostId,
        ]);

        return false;
    }

    /**
     * The WordPress slug, but only when it is fit to be a public URL.
     *
     * WordPress builds a slug from the title, and for a Thai title that yields a
     * long percent-encoded string ("%e0%b8%9b%e0%b8%a3..."); a trashed post gets
     * "__trashed" appended. Neither belongs in an address a citizen will see or
     * share, so both are reported as "no usable slug" and the caller falls back
     * to the rule the old admin screens used.
     */
    protected function cleanSlug(?string $slug): ?string
    {
        return ($slug !== null && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ? $slug : null;
    }

    /**
     * Reduce a value to plain text for columns the public site prints escaped.
     *
     * Paragraph and line breaks become newlines before the tags are removed, so
     * text pasted in from a formatted source keeps its line structure instead
     * of running together.
     */
    protected function plainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('#<\s*br\s*/?\s*>|</\s*(p|div|li|h[1-6])\s*>#i', "\n", $value);
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $value));
    }

    /**
     * Whether this payload represents content the public site should show.
     */
    protected function isPublished(array $payload): bool
    {
        return ($payload['status'] ?? 'draft') === 'published';
    }

    /**
     * Mirror a WordPress term onto the local `categories` row the public
     * listings filter by, and return its id.
     *
     * Categories here are scoped by content type, while a WordPress taxonomy is
     * shared across types — so one term legitimately maps to one row per type.
     * Each row is found in order of confidence: the term it is already linked
     * to, then a row with the same slug, then an unlinked row with the same
     * name. That last step matters at cut-over, where the same display name
     * exists under different slugs per type (a "วีซ่า" category for knowledge
     * articles and another for FAQs); without it the first sync would leave the
     * originals stranded and build a parallel set beside them.
     *
     * An adopted row keeps its own slug, because that slug appears in the
     * public filter URLs.
     *
     * @param  string $type The `categories.type` this content uses.
     */
    protected function resolveCategory(?array $term, string $type): ?int
    {
        if (! $term) {
            return null;
        }

        // WordPress always ships a default "Uncategorized" term; it carries no
        // meaning for this site's listings.
        if (in_array($term['slug'], ['uncategorized', 'uncategorised'], true)) {
            return null;
        }

        $category = Category::where('type', $type)->where('wp_term_id', $term['term_id'])->first()
            ?? Category::where('type', $type)->where('slug', $term['slug'])->first()
            ?? Category::where('type', $type)->whereNull('wp_term_id')->where('name_th', $term['name'])->first();

        if (! $category) {
            $category = new Category(['type' => $type, 'slug' => $term['slug']]);
        }

        $category->fill([
            'name_th'    => $term['name'],
            'name_en'    => $term['fields']['name_en'] ?? null,
            'sort_order' => (int) ($term['fields']['sort_order'] ?? 0),
            'is_active'  => true,
        ]);

        $category->type       = $type;
        $category->wp_term_id = $term['term_id'];
        $category->save();

        return $category->id;
    }
}
