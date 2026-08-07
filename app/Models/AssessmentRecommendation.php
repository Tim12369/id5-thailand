<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Admin-editable result content for one inferred assessment status.
 * Pairs with the config-side StatusDefinition (same status_key) — logic lives
 * in config, this holds the human-readable guidance officers maintain.
 */
class AssessmentRecommendation extends Model
{
    /** The localized content groups officers author (each has _th/_en/_zh columns). */
    public const CONTENT_FIELDS = [
        'legal_basis', 'validity_note', 'allowed_activities', 'restricted_activities',
        'deadlines', 'responsible_agency', 'important_notices',
    ];

    protected $fillable = [
        'status_key', 'risk_level',
        'legal_basis_th', 'legal_basis_en', 'legal_basis_zh',
        'validity_note_th', 'validity_note_en', 'validity_note_zh',
        'allowed_activities_th', 'allowed_activities_en', 'allowed_activities_zh',
        'restricted_activities_th', 'restricted_activities_en', 'restricted_activities_zh',
        'deadlines_th', 'deadlines_en', 'deadlines_zh',
        'responsible_agency_th', 'responsible_agency_en', 'responsible_agency_zh',
        'important_notices_th', 'important_notices_en', 'important_notices_zh',
        'suggested_persona', 'suggested_topics',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'suggested_topics' => 'array',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeForStatus(Builder $q, string $statusKey): Builder
    {
        return $q->where('status_key', $statusKey);
    }

    /**
     * Localized accessor for any of the _th/_en/_zh content fields, e.g.
     * ->localized('legal_basis'). Falls back TH first (the authoritative copy).
     */
    public function localized(string $field): ?string
    {
        $locale = App::getLocale();
        $value = $this->{"{$field}_{$locale}"} ?? null;

        return $value ?: ($this->{"{$field}_th"} ?? null);
    }

    /**
     * How many of the authored content groups have text in at least one locale.
     * Powers the "3/7 filled" progress hint on the admin index.
     */
    public function authoredCount(): int
    {
        return collect(self::CONTENT_FIELDS)
            ->filter(fn ($f) => filled($this->{"{$f}_th"}) || filled($this->{"{$f}_en"}) || filled($this->{"{$f}_zh"}))
            ->count();
    }

    /**
     * Split a newline/semicolon-separated content field into list items,
     * used for allowed/restricted activity bullet lists on the result page.
     */
    public function localizedList(string $field): array
    {
        $raw = $this->localized($field);

        if (! $raw) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|;/', $raw))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
