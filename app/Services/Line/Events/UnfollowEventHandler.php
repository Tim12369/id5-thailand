<?php

namespace App\Services\Line\Events;

use App\Models\LineSubscriber;

class UnfollowEventHandler
{
    /**
     * Handle an `unfollow`/block webhook event: mark the subscriber inactive
     * so they're excluded from future broadcast audiences. No reply is sent —
     * LINE does not provide a reply token for unfollow events.
     */
    public function handle(array $event): void
    {
        $lineUserId = $event['source']['userId'] ?? null;

        if (! $lineUserId) {
            return;
        }

        LineSubscriber::where('line_user_id', $lineUserId)->update([
            'status'        => 'unfollowed',
            'unfollowed_at' => now(),
        ]);
    }
}
