<?php

namespace App\Services\Line;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the LINE Messaging API REST endpoints, built on
 * Laravel's Http facade. Deliberately not a third-party SDK — keeps
 * the integration auditable and dependency-free for a government system.
 */
class LineMessagingClient
{
    private const BASE_URL = 'https://api.line.me/v2/bot';

    private function client()
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken((string) config('services.line.channel_access_token'))
            ->acceptJson();
    }

    /**
     * Reply to an inbound event using its one-shot reply token.
     *
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function reply(string $replyToken, array $messages): Response
    {
        return $this->client()->post('/message/reply', [
            'replyToken' => $replyToken,
            'messages'   => $messages,
        ]);
    }

    /**
     * Push a message to a specific user (not tied to a reply token).
     *
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function push(string $lineUserId, array $messages): Response
    {
        return $this->client()->post('/message/push', [
            'to'       => $lineUserId,
            'messages' => $messages,
        ]);
    }

    public function getProfile(string $lineUserId): Response
    {
        return $this->client()->get("/profile/{$lineUserId}");
    }

    /**
     * Build a simple text message payload.
     */
    public function textMessage(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }
}
