<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content',
        'structured_response', 'retrieved_sources',
        'tokens_used', 'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'structured_response' => 'array',
            'retrieved_sources'   => 'array',
            'tokens_used'         => 'integer',
            'response_time_ms'    => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
