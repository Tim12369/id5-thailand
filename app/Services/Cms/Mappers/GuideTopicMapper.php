<?php

namespace App\Services\Cms\Mappers;

use App\Models\GuideDocument;
use App\Models\GuideLocation;
use App\Models\GuideStep;
use App\Models\GuideTopic;
use App\Models\PublicForm;
use App\Models\Station;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Smart Guide topics.
 *
 * One WordPress screen stands in for four database tables: the topic itself,
 * its document checklist, its steps and its locations. Each of the three lists
 * is a repeater, so an editor adds a step by pressing "add" rather than opening
 * a second screen. (The per-audience guidance text has its own screen — see
 * GuideContentMapper.)
 *
 * The child rows are replaced wholesale on each save. They have no public URLs,
 * no counters and no inbound references, so rewriting them is simpler and more
 * predictable than diffing, and it keeps the order the editor dragged them into.
 */
class GuideTopicMapper extends Mapper
{
    /** Colour tones the site's stylesheet has classes for. */
    private const COLORS = ['blue', 'cyan', 'teal', 'green', 'indigo', 'purple', 'pink', 'red', 'orange', 'amber', 'slate'];

    /**
     * An icon as the guide pages expect it ("ti-passport"), from whatever an
     * editor typed ("passport", "ti-passport"), or null if the icon font has
     * no such icon.
     */
    private function icon(mixed $typed): ?string
    {
        $name = \App\Support\ItaIcon::normalise(is_string($typed) ? $typed : null);

        return $name ? 'ti-'.$name : null;
    }

    public function upsert(array $payload): ?Model
    {
        $topicKey = $this->field($payload, 'topic_key');

        if (! $topicKey) {
            return null;
        }

        if (! $this->mayWrite(GuideTopic::where('topic', $topicKey)->value('wp_post_id'), $payload)) {
            return null;
        }

        return DB::transaction(function () use ($payload, $topicKey) {
            $topic = GuideTopic::firstOrNew(['topic' => $topicKey]);

            $topic->fill([
                'topic'           => $topicKey,
                // The guide pages print these straight into class names, so only
                // values they can draw are taken; otherwise what is there stays.
                'icon'            => $this->icon($this->field($payload, 'icon')) ?? $topic->icon,
                'color'           => in_array($this->field($payload, 'color'), self::COLORS, true) ? $this->field($payload, 'color') : ($topic->color ?: 'blue'),
                'search_keywords' => $this->field($payload, 'search_keywords'),
                'deadline_days'   => $this->int($payload, 'deadline_days'),
                'is_urgent'       => $this->bool($payload, 'is_urgent'),
                'sort_order'      => $payload['menu_order'],
                'is_active'       => $this->bool($payload, 'is_active', true) && $this->isPublished($payload),
            ]);

            $topic->wp_post_id    = $payload['wp_post_id'];
            $topic->cms_synced_at = now();
            $topic->save();

            $this->syncDocuments($topic, $payload);
            $this->syncSteps($topic, $payload);
            $this->syncLocations($topic, $payload);

            return $topic;
        });
    }

    public function knownPostIds(string $postType): array
    {
        return GuideTopic::where('is_active', true)->whereNotNull('wp_post_id')->pluck('wp_post_id')->all();
    }

    public function delete(int $wpPostId): void
    {
        // Hide rather than destroy: the topic's child rows are referenced from
        // assessment results, and a missing topic would break those links.
        GuideTopic::where('wp_post_id', $wpPostId)->update(['is_active' => false]);
    }

    private function syncDocuments(GuideTopic $topic, array $payload): void
    {
        $topic->documents()->delete();

        foreach ($this->rows($payload, 'documents') as $index => $row) {
            if (blank($row['name_th'] ?? null)) {
                continue;
            }

            GuideDocument::create([
                'guide_topic_id' => $topic->id,
                'form_id'        => $this->formId($row['form_code'] ?? null),
                'name_th'        => $row['name_th'],
                'name_en'        => ($row['name_en'] ?? null) ?: $row['name_th'],
                'is_required'    => (bool) ($row['is_required'] ?? true),
                'sort_order'     => $index + 1,
            ]);
        }
    }

    private function syncSteps(GuideTopic $topic, array $payload): void
    {
        $topic->steps()->delete();

        foreach ($this->rows($payload, 'steps') as $index => $row) {
            if (blank($row['title_th'] ?? null)) {
                continue;
            }

            GuideStep::create([
                'guide_topic_id' => $topic->id,
                'step_number'    => $index + 1,
                'title_th'       => $row['title_th'],
                'title_en'       => ($row['title_en'] ?? null) ?: $row['title_th'],
                'description_th' => ($row['description_th'] ?? null) ?: null,
                'description_en' => ($row['description_en'] ?? null) ?: null,
                'sort_order'     => $index + 1,
            ]);
        }
    }

    private function syncLocations(GuideTopic $topic, array $payload): void
    {
        $topic->locations()->delete();

        foreach ($this->rows($payload, 'locations') as $index => $row) {
            if (blank($row['label_th'] ?? null)) {
                continue;
            }

            $type = $row['type'] ?? 'office';

            GuideLocation::create([
                'guide_topic_id' => $topic->id,
                'type'           => in_array($type, ['office', 'online', 'embassy', 'other'], true) ? $type : 'other',
                'label_th'       => $row['label_th'],
                'label_en'       => ($row['label_en'] ?? null) ?: $row['label_th'],
                'url'            => ($row['url'] ?? null) ?: null,
                'station_id'     => $this->stationId($row['office_code'] ?? null),
                'sort_order'     => $index + 1,
            ]);
        }
    }

    /** Resolve a form code typed by an editor to its row, if it exists. */
    private function formId(?string $code): ?int
    {
        return $code ? PublicForm::where('code', trim($code))->value('id') : null;
    }

    /** Resolve an office code typed by an editor to its row, if it exists. */
    private function stationId(?string $code): ?int
    {
        return $code ? Station::where('code', trim($code))->value('id') : null;
    }
}
