<?php

namespace App\Support\Assessment;

use App\Models\AssessmentRecommendation;

/**
 * The outcome of an assessment: the inferred primary status, its confidence,
 * a few close alternatives (used for the "not sure" multi-suggestion case),
 * and the admin-managed recommendation content for the primary status.
 *
 * This is an INITIAL, non-binding assessment — RecommendationBuilder always
 * pairs it with the mandatory disclaimer before it reaches the view.
 */
class AssessmentResult
{
    /**
     * @param  StatusDefinition  $status  primary inferred status
     * @param  int  $confidence  0-100 rough confidence of the primary status
     * @param  StatusDefinition[]  $alternatives  other plausible statuses (ranked)
     * @param  AssessmentRecommendation|null  $recommendation  DB content for $status
     */
    public function __construct(
        public readonly StatusDefinition $status,
        public readonly int $confidence,
        public readonly array $alternatives,
        public readonly ?AssessmentRecommendation $recommendation,
    ) {}

    public function isLowConfidence(): bool
    {
        return $this->confidence < 50;
    }

    /**
     * True when the answers didn't score against any of the 18 known
     * statuses (StatusDefinition::UNDETERMINED_KEY) -- the "no dead end"
     * fallback. Not one of the 18 official audiences; a distinct, honest
     * "we need more information" outcome rather than a forced guess.
     */
    public function isUndetermined(): bool
    {
        return $this->status->key === StatusDefinition::UNDETERMINED_KEY;
    }

    public function hasContent(): bool
    {
        return $this->recommendation !== null;
    }
}
