<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandCenterAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id', 'type', 'severity', 'title', 'message', 'location', 'status',
    ];

    public const TYPES = ['system', 'security', 'immigration', 'network', 'camera'];
    public const SEVERITIES = ['info', 'warning', 'critical'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', ['new', 'acknowledged']);
    }

    public function scopeForStation(Builder $q, int $stationId): Builder
    {
        return $q->where('station_id', $stationId);
    }

    public function scopeNewestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('created_at');
    }
}
