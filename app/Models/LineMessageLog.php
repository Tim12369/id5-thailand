<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_subscriber_id', 'direction', 'message_type', 'payload',
        'related_type', 'related_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(LineSubscriber::class, 'line_subscriber_id');
    }
}
