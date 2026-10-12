<?php

namespace App\Services\Cms\Mappers;

use App\Models\SmartGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Smart Guide audience guidance — the Who / What / Why / Where / When / How
 * answers shown for one audience on one topic.
 *
 * One WordPress entry covers one "audience + topic" pair and becomes up to six
 * `smart_guides` rows, one per question. That is the same shape the old admin
 * screen edited, so an administrator sees the same six boxes they always did.
 *
 * The answers are formatted text (bold, bullet lists) and the public page
 * prints them as HTML, so they travel as HTML end to end. An earlier version of
 * this integration carried them through plain-text boxes, which silently
 * removed every tag and ran list items together.
 */
class GuideContentMapper extends Mapper
{
    public function upsert(array $payload): ?Model
    {
        $persona = $this->field($payload, 'persona');
        $topic   = $this->field($payload, 'topic_key');

        if (! in_array($persona, SmartGuide::PERSONAS, true) || ! in_array($topic, SmartGuide::TOPICS, true)) {
            // Without a valid audience and topic there is no page to show it on.
            return null;
        }

        $owner = SmartGuide::where('persona', $persona)->where('topic', $topic)
            ->whereNotNull('wp_post_id')->value('wp_post_id');

        if (! $this->mayWrite($owner, $payload)) {
            return null;
        }

        return DB::transaction(function () use ($payload, $persona, $topic) {
            $last = null;

            foreach (SmartGuide::DIMENSIONS as $index => $dimension) {
                $thai = $this->field($payload, $dimension.'_th');
                $row  = SmartGuide::where('persona', $persona)
                    ->where('topic', $topic)
                    ->where('dimension', $dimension)
                    ->first();

                if (blank($thai)) {
                    // A question left empty is simply not shown; removing the
                    // row is how the public page knows to skip that heading.
                    $row?->delete();

                    continue;
                }

                $row ??= new SmartGuide([
                    'persona'   => $persona,
                    'topic'     => $topic,
                    'dimension' => $dimension,
                ]);

                $row->fill([
                    'content_th' => $thai,
                    'content_en' => $this->field($payload, $dimension.'_en'),
                    'sort_order' => $index + 1,
                    'is_active'  => $this->isPublished($payload),
                ]);

                $row->wp_post_id    = $payload['wp_post_id'];
                $row->cms_synced_at = now();
                $row->save();

                $last = $row;
            }

            return $last;
        });
    }

    public function knownPostIds(string $postType): array
    {
        return SmartGuide::where('is_active', true)->whereNotNull('wp_post_id')->distinct()->pluck('wp_post_id')->all();
    }

    /**
     * Hide rather than destroy, so an entry deleted by mistake in WordPress and
     * then restored from its trash comes back with nothing lost.
     */
    public function delete(int $wpPostId): void
    {
        SmartGuide::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }
}
