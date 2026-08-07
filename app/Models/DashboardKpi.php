<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardKpi extends Model
{
    protected $fillable = [
        'period_id', 'metric_key', 'value', 'previous_value', 'unit', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'previous_value' => 'float',
            'meta' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(DashboardPeriod::class, 'period_id');
    }

    public function changePercent(): ?float
    {
        if ($this->previous_value === null || $this->previous_value == 0) {
            return null;
        }
        return round((($this->value - $this->previous_value) / $this->previous_value) * 100, 1);
    }

    public function isPositiveChange(): bool
    {
        return $this->value >= ($this->previous_value ?? $this->value);
    }

    public function formattedValue(): string
    {
        if ($this->unit === 'percent') {
            return number_format($this->value, 1) . '%';
        }
        return number_format((int) $this->value);
    }
}
