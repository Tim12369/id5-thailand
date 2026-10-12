<?php

namespace App\Support\Assessment;

use App\Support\CmsText;

/**
 * Immutable value object for one status from config/assessment/statuses.php,
 * plus the scoring rule that ranks it against a user's answers.
 */
class StatusDefinition
{
    /**
     * Not one of the 18 config-defined statuses -- a distinct safety-net
     * outcome used only when an answer set scores zero against all of them
     * (see RecommendationBuilder::build()), so the wizard never dead-ends.
     */
    public const UNDETERMINED_KEY = 'undetermined';

    public function __construct(
        public readonly string $key,
        public readonly array $label,          // ['th' => .., 'en' => ..]
        public readonly string $icon,
        public readonly string $color,
        public readonly ?string $persona,      // existing SmartGuide persona
        public readonly array $topics,         // existing GuideTopic slugs
        public readonly array $signals,        // answerKey => [answerValue => weight]
    ) {}

    public static function fromArray(string $key, array $data): self
    {
        return new self(
            key: $key,
            label: $data['label'],
            icon: $data['icon'] ?? 'ti-user',
            color: $data['color'] ?? 'blue',
            persona: $data['persona'] ?? null,
            topics: $data['topics'] ?? [],
            signals: $data['signals'] ?? [],
        );
    }

    public static function undetermined(): self
    {
        return new self(
            key: self::UNDETERMINED_KEY,
            label: [
                'th' => 'ยังไม่สามารถระบุกลุ่มที่ชัดเจนได้',
                'en' => 'Unable to Determine a Clear Match',
            ],
            icon: 'ti-help-circle',
            color: 'slate',
            persona: 'general',
            topics: [],
            signals: [],
        );
    }

    public function localizedLabel(): string
    {
        return CmsText::localized('assessment_status.'.$this->key, $this->label ?: [$this->key]);
    }

    /**
     * Score this status against an answer set by summing matched signal weights.
     * Handles both single-value answers and multi-select (array) answers.
     */
    public function score(array $answers): int
    {
        $total = 0;

        foreach ($this->signals as $answerKey => $valueWeights) {
            $answer = $answers[$answerKey] ?? null;

            if ($answer === null) {
                continue;
            }

            foreach ($valueWeights as $value => $weight) {
                $matched = is_array($answer)
                    ? in_array($value, $answer, true)
                    : $answer === $value;

                if ($matched) {
                    $total += (int) $weight;
                }
            }
        }

        return $total;
    }
}
