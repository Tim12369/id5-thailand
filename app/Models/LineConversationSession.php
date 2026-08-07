<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineConversationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_subscriber_id', 'context', 'state', 'last_intent', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'state'      => 'array',
            'expires_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(LineSubscriber::class, 'line_subscriber_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $q): Builder
    {
        return $q->where('expires_at', '<=', now());
    }
}
