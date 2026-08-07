<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_event_id', 'event_hash', 'line_user_id', 'event_type',
        'raw_payload', 'status', 'processed_at', 'error_message', 'reply_token',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload'  => 'array',
            'processed_at' => 'datetime',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeUnprocessed(Builder $q): Builder
    {
        return $q->whereIn('status', ['received', 'failed']);
    }
}
