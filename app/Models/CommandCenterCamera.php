<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandCenterCamera extends Model
{
    use HasFactory;

    public const READINESS_NOT_READY = 'not_ready';
    public const READINESS_PENDING_REVIEW = 'pending_review';
    public const READINESS_READY = 'ready';

    protected $fillable = [
        'station_id', 'name', 'location', 'stream_url', 'status', 'last_seen',
        'manufacturer', 'model', 'cloud_app', 'ip_address', 'rtsp_port', 'rtsp_path', 'snapshot_url',
        'onvif_port', 'credentials_encrypted', 'integration_readiness', 'readiness_notes',
    ];

    // Never serialized to the browser -- the backend alone talks to the
    // media server; credentials have no reason to leave the server, even
    // encrypted-at-rest.
    protected $hidden = ['credentials_encrypted'];

    // Computed once here so the frontend never has to re-derive "is this
    // camera actually streamable" from raw columns -- one source of truth.
    protected $appends = ['is_integration_ready'];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'credentials_encrypted' => 'encrypted',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function scopeOnline(Builder $q): Builder
    {
        return $q->where('status', 'online');
    }

    public function scopeOffline(Builder $q): Builder
    {
        return $q->where('status', 'offline');
    }

    public function scopeForStation(Builder $q, int $stationId): Builder
    {
        return $q->where('station_id', $stationId);
    }

    /**
     * True only once real network facts have been confirmed AND marked
     * ready (see the CCTV Integration Feasibility Assessment) -- naming a
     * cloud app or having a stream_url alone is not sufficient. This is the
     * single source of truth the UI uses to decide whether to attempt a
     * live player or show an honest "unavailable" state.
     */
    public function isIntegrationReady(): bool
    {
        return $this->integration_readiness === self::READINESS_READY
            && ! empty($this->ip_address)
            && ! empty($this->rtsp_path);
    }

    public function getIsIntegrationReadyAttribute(): bool
    {
        return $this->isIntegrationReady();
    }
}
