<?php

namespace App\Services\Cms\Mappers;

use App\Models\AssessmentRecommendation;
use Illuminate\Database\Eloquent\Model;

/**
 * The guidance shown at the end of the "Who are you?" assessment.
 *
 * Branching and scoring stay in `config/assessment/*` — code, not content. Only
 * the human-readable result text is editable, and it is keyed by `status_key`,
 * which must match a status the config defines. The WordPress field is a fixed
 * dropdown rather than free text so an editor cannot create a result that the
 * assessment will never reach.
 */
class AssessmentMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        $statusKey = $this->field($payload, 'status_key');

        if (! $statusKey) {
            return null;
        }

        $known = array_keys(config('assessment.statuses', []));

        if ($known && ! in_array($statusKey, $known, true)) {
            // The assessment can never produce this status, so saving the text
            // would create content no visitor could reach.
            return null;
        }

        $recommendation = AssessmentRecommendation::firstOrNew(['status_key' => $statusKey]);

        if (! $this->mayWrite($recommendation->wp_post_id, $payload)) {
            return null;
        }

        $attributes = [
            'status_key'        => $statusKey,
            'risk_level'        => $this->field($payload, 'risk_level', 'info'),
            'suggested_persona' => $this->field($payload, 'suggested_persona'),
            'suggested_topics'  => $this->topics($payload),
            'is_active'         => $this->bool($payload, 'is_active', true) && $this->isPublished($payload),
            'sort_order'        => $payload['menu_order'],
        ];

        foreach (AssessmentRecommendation::CONTENT_FIELDS as $field) {
            $attributes[$field.'_th'] = $this->field($payload, $field.'_th');
            $attributes[$field.'_en'] = $this->field($payload, $field.'_en');
        }

        $recommendation->fill($attributes);

        $recommendation->wp_post_id    = $payload['wp_post_id'];
        $recommendation->cms_synced_at = now();
        $recommendation->save();

        return $recommendation;
    }

    public function knownPostIds(string $postType): array
    {
        return AssessmentRecommendation::where('is_active', true)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    public function delete(int $wpPostId): void
    {
        AssessmentRecommendation::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }

    /**
     * A comma-separated list of Smart Guide topics, normalised to the array the
     * model casts to JSON. An empty list means "use the config defaults".
     */
    private function topics(array $payload): ?array
    {
        $raw = $this->field($payload, 'suggested_topics');

        if (! $raw) {
            return null;
        }

        $topics = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));

        return $topics ?: null;
    }
}
