<?php

namespace App\Services\Cms\Mappers;

use App\Models\PublicForm;
use Illuminate\Database\Eloquent\Model;

/**
 * Downloadable forms, and the subset of them shown on the About page.
 *
 * The PDF itself is mirrored onto the **private** disk, not the public one, so
 * it keeps being served through the existing throttled download route
 * (Public\FormDownloadController) rather than becoming a directly linkable file.
 * That preserves both the download counter and the rate limiting.
 */
class FormMapper extends Mapper
{
    /**
     * The category keys the public downloads page groups by. Anything else
     * falls back to "other" so an unexpected term never hides a form.
     */
    private const CATEGORIES = ['visa', 'entry_exit', 'reporting', 'extension', 'work_permit', 'other'];

    public function upsert(array $payload): ?Model
    {
        $code = $this->field($payload, 'code');

        if (! $code) {
            // `code` is the business key and is required by the schema; without
            // it the row cannot be addressed, so skip rather than guess.
            return null;
        }

        $form = PublicForm::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        // An earlier non-WordPress row with the same code is adopted rather than
        // duplicated, which is what makes the first import non-destructive.
        if (! $form->exists) {
            $existing = PublicForm::where('code', $code)->first();

            if ($existing) {
                // Already owned by another WordPress item: only a published
                // replacement may take the code over (see mayWrite()).
                if (! $this->mayWrite($existing->wp_post_id, $payload)) {
                    return null;
                }

                $form = $existing;
            }
        }

        $file = $this->attachment($payload, 'file');

        // The download route always serves the file as "<code>_<version>.pdf",
        // so anything that is not a PDF would reach visitors as a corrupt
        // document. Treat it as no file at all.
        if ($file && ($file['mime_type'] ?? '') !== 'application/pdf') {
            $file = null;
        }

        $path = $this->media->mirrorPrivate($file, $form->path);

        // A form with no file attached yet cannot be downloaded, so it is never
        // offered on the public downloads page however it is flagged in
        // WordPress — better an absent entry than a broken download link.
        $hasFile = filled($path);

        $form->fill([
            'code'               => $code,
            'name_th'            => $payload['title'] ?: $code,
            'name_en'            => $this->field($payload, 'name_en') ?? $payload['title'] ?? $code,
            'description_th'     => $this->field($payload, 'description_th'),
            'description_en'     => $this->field($payload, 'description_en'),
            'category'           => $this->category($payload),
            'version'            => $this->field($payload, 'version', '1.0'),
            'is_active'          => $hasFile && $this->bool($payload, 'is_active', true) && $this->isPublished($payload),
            'show_on_about_page' => $this->bool($payload, 'show_on_about_page'),
            'sort_order'         => $payload['menu_order'],
            // `path` is a non-null column, so a form awaiting its first upload
            // is stored with an empty path rather than failing the whole sync.
            'path'               => $path ?? '',
        ]);

        if ($hasFile) {
            $form->disk = $this->media->privateDisk();

            if (! empty($file['size'])) {
                $form->file_size_bytes = (int) $file['size'];
            }
        }

        // download_count is this application's own counter and is never reset.
        $form->wp_post_id    = $payload['wp_post_id'];
        $form->cms_synced_at = now();
        $form->save();

        return $form;
    }

    public function knownPostIds(string $postType): array
    {
        return PublicForm::where('is_active', true)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    public function delete(int $wpPostId): void
    {
        PublicForm::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }

    private function category(array $payload): string
    {
        $term = $this->term($payload, 'id5_form_category');
        $slug = $term['slug'] ?? 'other';

        return in_array($slug, self::CATEGORIES, true) ? $slug : 'other';
    }
}
