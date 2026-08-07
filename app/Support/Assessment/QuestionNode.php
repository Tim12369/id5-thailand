<?php

namespace App\Support\Assessment;

use Illuminate\Support\Facades\App;

/**
 * Immutable value object wrapping one question from config/assessment/questions.php.
 * Kept deliberately thin — the branching interpreter (client + QuestionFlowService)
 * reads showIf; this just gives typed access and localization.
 */
class QuestionNode
{
    public function __construct(
        public readonly string $id,
        public readonly int $stage,
        public readonly string $type,          // 'single' | 'multi'
        public readonly array $title,          // ['th' => .., 'en' => ..]
        public readonly ?array $help,
        public readonly ?array $showIf,        // answerKey => [allowedValues] | null
        public readonly array $options,        // raw option arrays
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            stage: (int) $data['stage'],
            type: $data['type'] ?? 'single',
            title: $data['title'],
            help: $data['help'] ?? null,
            showIf: $data['show_if'] ?? null,
            options: $data['options'] ?? [],
        );
    }

    public function localizedTitle(): string
    {
        return $this->localize($this->title);
    }

    public function localizedHelp(): ?string
    {
        return $this->help ? $this->localize($this->help) : null;
    }

    /**
     * Is this node visible given the current answer set? Mirrors the exact rule
     * the client interpreter uses, so server validation and the UI never diverge.
     */
    public function isVisible(array $answers): bool
    {
        if (empty($this->showIf)) {
            return true;
        }

        foreach ($this->showIf as $key => $allowed) {
            $answer = $answers[$key] ?? null;

            if (is_array($answer)) {
                // multi-select answer: visible if any selected value is allowed
                if (empty(array_intersect($answer, $allowed))) {
                    return false;
                }
            } elseif (! in_array($answer, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Localized labels of the options matching the given selected value(s).
     * Used to render a human-readable recap of what the user answered.
     *
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    public function selectedLabels(array $values): array
    {
        $labels = [];

        foreach ($this->options as $opt) {
            if (in_array($opt['value'], $values, true)) {
                $labels[] = $this->localize($opt['label']);
            }
        }

        return $labels;
    }

    /**
     * The option payload with each label pre-localized, for the client wizard.
     */
    public function optionsForClient(): array
    {
        return array_map(fn (array $opt) => [
            'value' => $opt['value'],
            'icon' => $opt['icon'] ?? 'ti-point',
            'label' => $this->localize($opt['label']),
        ], $this->options);
    }

    private function localize(array $bag): string
    {
        $locale = App::getLocale();

        return $bag[$locale] ?? $bag['en'] ?? $bag['th'] ?? (string) reset($bag);
    }
}
