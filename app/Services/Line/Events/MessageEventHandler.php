<?php

namespace App\Services\Line\Events;

use App\Models\LineMessageLog;
use App\Models\LineSubscriber;
use App\Services\Line\LineMessagingClient;

class MessageEventHandler
{
    public function __construct(private LineMessagingClient $client) {}

    /**
     * Handle a `message` webhook event.
     *
     * Phase 2 scope: a simple echo reply ("You said: <text>") that proves
     * the receive → process → reply round trip works end to end. Intent
     * resolution (FAQ, Smart Guide, Knowledge Base, Auto Reply, ...) is
     * layered on top of this in later phases — see IntentResolver.
     */
    public function handle(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;
        $message    = $event['message'] ?? [];

        if (! $lineUserId || ! $replyToken) {
            return;
        }

        $subscriber = LineSubscriber::where('line_user_id', $lineUserId)->first();
        $subscriber?->update(['last_interacted_at' => now()]);

        if (($message['type'] ?? null) !== 'text') {
            return;
        }

        $text = (string) ($message['text'] ?? '');
        $reply = $this->client->textMessage("You said: {$text}");

        $this->client->reply($replyToken, [$reply]);

        LineMessageLog::create([
            'line_subscriber_id' => $subscriber?->id,
            'direction'          => 'outbound',
            'message_type'       => 'text',
            'payload'            => $reply,
        ]);
    }
}
