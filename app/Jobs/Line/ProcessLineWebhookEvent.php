<?php

namespace App\Jobs\Line;

use App\Models\LineWebhookEvent;
use App\Services\Line\Events\FollowEventHandler;
use App\Services\Line\Events\MessageEventHandler;
use App\Services\Line\Events\UnfollowEventHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessLineWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $webhookEventId) {}

    public function handle(
        FollowEventHandler $followHandler,
        UnfollowEventHandler $unfollowHandler,
        MessageEventHandler $messageHandler,
    ): void {
        $record = LineWebhookEvent::find($this->webhookEventId);

        if (! $record || $record->status === 'processed') {
            return;
        }

        $record->update(['status' => 'processing']);

        try {
            match ($record->event_type) {
                'follow'   => $followHandler->handle($record->raw_payload),
                'unfollow' => $unfollowHandler->handle($record->raw_payload),
                'message'  => $messageHandler->handle($record->raw_payload),
                default    => $record->update(['status' => 'ignored']),
            };

            if ($record->status !== 'ignored') {
                $record->update(['status' => 'processed', 'processed_at' => now()]);
            }
        } catch (Throwable $e) {
            $record->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
