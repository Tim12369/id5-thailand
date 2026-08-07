<?php

namespace App\Services\Line\Events;

use App\Models\LineSubscriber;
use App\Services\Line\LineMessagingClient;

class FollowEventHandler
{
    public function __construct(private LineMessagingClient $client) {}

    /**
     * Handle a `follow` webhook event: upsert the subscriber, cache their
     * profile, and send a localized welcome reply.
     */
    public function handle(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;

        if (! $lineUserId) {
            return;
        }

        $profile = $this->fetchProfile($lineUserId);

        $subscriber = LineSubscriber::updateOrCreate(
            ['line_user_id' => $lineUserId],
            array_filter([
                'display_name'       => $profile['displayName'] ?? null,
                'picture_url'        => $profile['pictureUrl'] ?? null,
                'status_message'     => $profile['statusMessage'] ?? null,
                'line_locale'        => $profile['language'] ?? null,
                'status'             => 'active',
                'followed_at'        => now(),
                'unfollowed_at'      => null,
                'last_interacted_at' => now(),
            ], fn ($value) => $value !== null)
        );

        if ($replyToken) {
            $this->client->reply($replyToken, [
                $this->client->textMessage(
                    "Welcome, {$subscriber->display_name}! Thanks for adding ID5 @ Thailand 5.0. Send any message to get started."
                ),
            ]);
        }
    }

    private function fetchProfile(string $lineUserId): array
    {
        $response = $this->client->getProfile($lineUserId);

        return $response->successful() ? $response->json() : [];
    }
}
