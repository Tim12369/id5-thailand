<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandCenterIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_code', 'type', 'priority', 'station_id', 'location',
        'description', 'status', 'reported_by',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['open', 'investigating', 'resolved'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function scopeOpenOrInvestigating(Builder $q): Builder
    {
        return $q->whereIn('status', ['open', 'investigating']);
    }

    public function scopeForStation(Builder $q, int $stationId): Builder
    {
        return $q->where('station_id', $stationId);
    }

    public static function generateCode(): string
    {
        return 'INC-' . now()->format('Ymd') . '-' . str_pad((string) (static::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}
