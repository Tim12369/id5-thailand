<?php

namespace App\Services\Assessment;

use App\Support\Assessment\StatusDefinition;

/**
 * Scores a completed answer set against every config status and returns them
 * ranked. This is the ONLY place inference happens — it runs server-side so the
 * client never re-implements (or can tamper with) the scoring.
 *
 * It never asks the user to declare a legal status directly; it infers likely
 * categories purely from the factual answers, and the result is always framed
 * as an initial, non-binding assessment.
 */
class StatusInferenceEngine
{
    /** @var StatusDefinition[]|null */
    private ?array $statuses = null;

    /** @return StatusDefinition[] */
    public function statuses(): array
    {
        return $this->statuses ??= collect(config('assessment.statuses', []))
            ->map(fn (array $def, string $key) => StatusDefinition::fromArray($key, $def))
            ->values()
            ->all();
    }

    public function find(string $key): ?StatusDefinition
    {
        foreach ($this->statuses() as $status) {
            if ($status->key === $key) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Rank statuses for an answer set, highest score first, dropping zero-scores.
     *
     * @return array<int, array{status: StatusDefinition, score: int}>
     */
    public function rank(array $answers): array
    {
        $scored = [];

        foreach ($this->statuses() as $status) {
            $score = $status->score($answers);
            if ($score > 0) {
                $scored[] = ['status' => $status, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored;
    }

    /**
     * Rough 0-100 confidence for the top status: how dominant its score is
     * relative to the total scored weight. Used to decide when to present the
     * "you might be one of these" multi-suggestion (low-confidence) result.
     */
    public function confidence(array $ranked): int
    {
        if (empty($ranked)) {
            return 0;
        }

        $top = $ranked[0]['score'];
        $total = array_sum(array_column($ranked, 'score'));

        if ($total === 0) {
            return 0;
        }

        return (int) round(($top / $total) * 100);
    }
}
