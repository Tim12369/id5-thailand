<?php

namespace App\Services\Content;

use App\Models\GuideTopic;
use App\Models\SmartGuide;
use Illuminate\Support\Collection;

class SmartGuideService
{
    /**
     * Get all SW1H entries for a persona + topic combination,
     * indexed by dimension for easy Blade access.
     *
     * Returns: ['who' => SmartGuide, 'what' => SmartGuide, ...]
     */
    public function getForPersonaAndTopic(string $persona, string $topic): array
    {
        $entries = SmartGuide::active()
            ->forPersona($persona)
            ->forTopic($topic)
            ->orderBy('sort_order')
            ->get();

        $indexed = [];
        foreach (SmartGuide::DIMENSIONS as $dim) {
            $indexed[$dim] = $entries->firstWhere('dimension', $dim);
        }

        return $indexed;
    }

    /**
     * Return the list of topics that have at least one entry for this persona.
     */
    public function getAvailableTopics(string $persona): Collection
    {
        return SmartGuide::active()
            ->forPersona($persona)
            ->distinct()
            ->pluck('topic')
            ->values();
    }

    /**
     * Map every persona to the list of topics that actually have content,
     * e.g. ['tourist' => ['visa'], 'student' => ['90day'], ...].
     * Used by the index page to only show topic tiles that lead to real content.
     */
    public function getTopicsByPersona(): array
    {
        return SmartGuide::active()
            ->select('persona', 'topic')
            ->distinct()
            ->get()
            ->groupBy('persona')
            ->map(fn ($rows) => $rows->pluck('topic')->values())
            ->toArray();
    }

    /**
     * Structured topic metadata (icon, color, deadline) shared across all personas.
     */
    public function getTopicMeta(string $topic): ?GuideTopic
    {
        return GuideTopic::active()->forTopic($topic)->first();
    }

    /**
     * Structured WHAT checklist + HOW steps + WHERE locations, shared across personas.
     * Returns ['documents' => Collection, 'steps' => Collection, 'locations' => Collection]
     */
    public function getStructuredContent(string $topic): array
    {
        $guideTopic = GuideTopic::active()
            ->forTopic($topic)
            ->with(['documents.form', 'steps', 'locations.station'])
            ->first();

        return [
            'documents' => $guideTopic?->documents ?? collect(),
            'steps'     => $guideTopic?->steps ?? collect(),
            'locations' => $guideTopic?->locations ?? collect(),
        ];
    }

    /**
     * Combines getTopicMeta() + getStructuredContent() into a single query for
     * callers that need both (e.g. the assessment result page) -- avoids
     * fetching the same GuideTopic row twice.
     *
     * Returns ['meta' => ?GuideTopic, 'structured' => ['documents' => .., 'steps' => .., 'locations' => ..]]
     */
    public function getTopicWithStructuredContent(string $topic): array
    {
        $guideTopic = GuideTopic::active()
            ->forTopic($topic)
            ->with(['documents.form', 'steps', 'locations.station'])
            ->first();

        return [
            'meta' => $guideTopic,
            'structured' => [
                'documents' => $guideTopic?->documents ?? collect(),
                'steps'     => $guideTopic?->steps ?? collect(),
                'locations' => $guideTopic?->locations ?? collect(),
            ],
        ];
    }

    /**
     * Search topics by keyword (matches topic name, search_keywords, or topic label).
     * Returns matching topic slugs, e.g. ['visa', 'work_permit'].
     */
    public function searchTopics(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $like = '%' . $query . '%';

        $matched = GuideTopic::active()
            ->where(function ($q) use ($like) {
                $q->where('topic', 'like', $like)
                  ->orWhere('search_keywords', 'like', $like);
            })
            ->orderBy('sort_order')
            ->pluck('topic')
            ->values()
            ->all();

        if (!empty($matched)) {
            return $matched;
        }

        // Fallback: match against translated topic labels (e.g. "TM30 Form", "วีซ่า")
        return array_values(array_filter(SmartGuide::TOPICS, function ($topic) use ($query) {
            $label = __("topics.{$topic}");
            return stripos($label, $query) !== false;
        }));
    }

    /**
     * Validate that a persona and topic string are known values.
     */
    public function isValidPersona(string $persona): bool
    {
        return in_array($persona, SmartGuide::PERSONAS);
    }

    public function isValidTopic(string $topic): bool
    {
        return in_array($topic, SmartGuide::TOPICS);
    }
}
