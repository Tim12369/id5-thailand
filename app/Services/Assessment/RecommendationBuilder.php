<?php

namespace App\Services\Assessment;

use App\Models\AssessmentRecommendation;
use App\Support\Assessment\AssessmentResult;
use App\Support\Assessment\StatusDefinition;

/**
 * Turns a set of answers into a full AssessmentResult: runs inference, loads the
 * admin-managed recommendation content for the winning status, and attaches a
 * short list of alternatives for the low-confidence / "not sure" case.
 *
 * Always returns a result -- never null -- so the wizard can never dead-end.
 * An answer set that scores zero against every one of the 18 statuses (e.g. a
 * "Not sure" user who answers every screener question "unsure") gets the
 * honest StatusDefinition::undetermined() outcome instead of silently
 * bouncing the user to an unrelated page.
 */
class RecommendationBuilder
{
    public function __construct(private StatusInferenceEngine $engine) {}

    public function build(array $answers): AssessmentResult
    {
        $ranked = $this->engine->rank($answers);

        if (empty($ranked)) {
            return new AssessmentResult(
                status: StatusDefinition::undetermined(),
                confidence: 0,
                alternatives: [],
                recommendation: null,
            );
        }

        $primary = $ranked[0]['status'];
        $confidence = $this->engine->confidence($ranked);

        $alternatives = collect($ranked)
            ->slice(1, 2)
            ->map(fn ($row) => $row['status'])
            ->all();

        $recommendation = AssessmentRecommendation::active()
            ->forStatus($primary->key)
            ->first();

        return new AssessmentResult(
            status: $primary,
            confidence: $confidence,
            alternatives: $alternatives,
            recommendation: $recommendation,
        );
    }
}
