<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_user_id', 'display_name', 'picture_url', 'status_message',
        'line_locale', 'preferred_locale', 'user_id', 'status',
        'followed_at', 'unfollowed_at', 'last_interacted_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'followed_at'        => 'datetime',
            'unfollowed_at'      => 'datetime',
            'last_interacted_at' => 'datetime',
            'metadata'           => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LineConversationSession::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(LineMessageLog::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }
}
