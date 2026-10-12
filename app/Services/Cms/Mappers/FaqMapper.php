<?php

namespace App\Services\Cms\Mappers;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;

/**
 * Frequently asked questions.
 *
 * The WordPress post title is the Thai question and the editor body is the Thai
 * answer, which keeps the most common edit — fixing the wording of an answer —
 * in WordPress's own familiar editor rather than in a custom field.
 */
class FaqMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        $faq = Faq::firstOrNew(['wp_post_id' => $payload['wp_post_id']]);

        $faq->fill([
            'question_th' => $payload['title'] ?: '',
            'question_en' => $this->field($payload, 'question_en'),
            'answer_th'   => $this->html($payload, $payload['content'] ?? ''),
            'answer_en'   => $this->html($payload, $this->field($payload, 'answer_en')),
            'is_public'   => true,
            // A draft in WordPress is simply an FAQ that is not live yet.
            'is_active'   => $this->isPublished($payload),
            'sort_order'  => $payload['menu_order'],
            'category_id' => $this->resolveCategory($this->term($payload, 'id5_content_category'), 'faq'),
        ]);

        $faq->wp_post_id    = $payload['wp_post_id'];
        $faq->cms_synced_at = now();
        $faq->save();

        return $faq;
    }

    public function knownPostIds(string $postType): array
    {
        return Faq::where('is_active', true)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    public function delete(int $wpPostId): void
    {
        // FAQs have no soft deletes, so deleting in WordPress hides the entry
        // rather than destroying the row and its view count.
        Faq::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }
}
