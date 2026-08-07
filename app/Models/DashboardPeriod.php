<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardPeriod extends Model
{
    protected $fillable = [
        'period_type', 'period_date', 'label_th', 'label_en', 'is_latest',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'is_latest' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function kpis(): HasMany
    {
        return $this->hasMany(DashboardKpi::class, 'period_id');
    }

    public function nationalityStats(): HasMany
    {
        return $this->hasMany(DashboardNationalityStat::class, 'period_id')->orderBy('rank');
    }

    public function categoryStats(): HasMany
    {
        return $this->hasMany(DashboardCategoryStat::class, 'period_id')->orderBy('sort_order');
    }

    public function provincialStats(): HasMany
    {
        return $this->hasMany(DashboardProvincialStat::class, 'period_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeLatest(Builder $query): Builder
    {
        return $query->where('is_latest', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('period_type', $type);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function label(): string
    {
        return $this->label_en ?: $this->label_th ?: $this->period_date->format('F Y');
    }

    public function getKpi(string $key): ?DashboardKpi
    {
        return $this->kpis->firstWhere('metric_key', $key);
    }
}
