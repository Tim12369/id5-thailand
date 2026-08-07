<?php

namespace App\Services\Assessment;

use App\Support\Assessment\QuestionNode;

/**
 * Loads the config question tree and exposes it as typed nodes plus a
 * client-ready payload. This is the single source of truth for both server-side
 * validation and the client Alpine interpreter — they read the same rules, so
 * branching never diverges between the two.
 */
class QuestionFlowService
{
    /** @var QuestionNode[]|null */
    private ?array $nodes = null;

    /** @return QuestionNode[] */
    public function nodes(): array
    {
        return $this->nodes ??= array_map(
            fn (array $q) => QuestionNode::fromArray($q),
            config('assessment.questions', [])
        );
    }

    public function stageCount(): int
    {
        return collect($this->nodes())->max(fn (QuestionNode $n) => $n->stage) ?? 0;
    }

    public function find(string $id): ?QuestionNode
    {
        foreach ($this->nodes() as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Nodes for a given stage that are visible for the current answer set.
     *
     * @return QuestionNode[]
     */
    public function visibleForStage(int $stage, array $answers): array
    {
        return array_values(array_filter(
            $this->nodes(),
            fn (QuestionNode $n) => $n->stage === $stage && $n->isVisible($answers)
        ));
    }

    /**
     * The whole tree serialized for the client wizard: pre-localized labels +
     * the raw showIf rules the generic Alpine interpreter evaluates.
     */
    public function clientPayload(): array
    {
        return array_map(fn (QuestionNode $n) => [
            'id' => $n->id,
            'stage' => $n->stage,
            'type' => $n->type,
            'title' => $n->localizedTitle(),
            'help' => $n->localizedHelp(),
            'showIf' => $n->showIf,
            'options' => $n->optionsForClient(),
        ], $this->nodes());
    }

    /**
     * A human-readable recap of the user's answers (question → chosen labels),
     * in stage order, for the result page. Only visible, answered questions.
     *
     * @return array<int, array{question: string, answers: array<int, string>}>
     */
    public function describeAnswers(array $answers): array
    {
        $recap = [];

        foreach ($this->nodes() as $node) {
            if (! array_key_exists($node->id, $answers) || ! $node->isVisible($answers)) {
                continue;
            }

            $values = is_array($answers[$node->id]) ? $answers[$node->id] : [$answers[$node->id]];
            $labels = $node->selectedLabels($values);

            if (! empty($labels)) {
                $recap[] = ['question' => $node->localizedTitle(), 'answers' => $labels];
            }
        }

        return $recap;
    }

    /**
     * Keep only answers that correspond to currently-visible questions, dropping
     * stale answers from branches the user backed out of. Also normalizes each
     * answer to the question's type (scalar for single, array for multi).
     */
    public function pruneAnswers(array $answers): array
    {
        $clean = [];

        foreach ($this->nodes() as $node) {
            if (! array_key_exists($node->id, $answers)) {
                continue;
            }
            if (! $node->isVisible($clean + $answers)) {
                continue;
            }

            $value = $answers[$node->id];

            if ($node->type === 'multi') {
                $clean[$node->id] = array_values(array_filter((array) $value, 'is_string'));
            } elseif (is_scalar($value)) {
                $clean[$node->id] = (string) $value;
            }
        }

        return $clean;
    }
}
