<?php

namespace App\Http\Controllers\Line;

use App\Http\Controllers\Controller;
use App\Jobs\Line\ProcessLineWebhookEvent;
use App\Models\LineWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    /**
     * Receive a verified LINE webhook payload.
     *
     * Kept intentionally thin: log each event for idempotency/audit, hand
     * off processing to a queued job, and acknowledge immediately — LINE
     * expects a fast 200 OK and will retry on slow/non-200 responses.
     */
    public function handle(Request $request): Response
    {
        $events = $request->input('events', []);

        Log::info('line.webhook.controller.start', ['event_count' => count($events)]);

        foreach ($events as $event) {
            $this->logAndDispatch($event);
        }

        Log::info('line.webhook.controller.responding', ['status' => 200]);

        return response('', 200);
    }

    private function logAndDispatch(array $event): void
    {
        $lineEventId = $event['webhookEventId'] ?? null;
        $hash = hash('sha256', json_encode($event));

        Log::info('line.webhook.event.logging.start', [
            'type'          => $event['type'] ?? 'unknown',
            'line_event_id' => $lineEventId,
            'hash'          => substr($hash, 0, 12),
        ]);

        $existing = LineWebhookEvent::query()
            ->when($lineEventId, fn ($q) => $q->where('line_event_id', $lineEventId))
            ->when(! $lineEventId, fn ($q) => $q->where('event_hash', $hash))
            ->first();

        if ($existing) {
            Log::info('line.webhook.event.duplicate.skipped', ['existing_id' => $existing->id]);

            return;
        }

        $record = LineWebhookEvent::create([
            'line_event_id' => $lineEventId,
            'event_hash'    => $hash,
            'line_user_id'  => $event['source']['userId'] ?? null,
            'event_type'    => $event['type'] ?? 'unknown',
            'raw_payload'   => $event,
            'status'        => 'received',
            'reply_token'   => $event['replyToken'] ?? null,
        ]);

        Log::info('line.webhook.event.logging.done', ['id' => $record->id]);

        Log::info('line.webhook.job.dispatch.start', ['id' => $record->id]);

        ProcessLineWebhookEvent::dispatch($record->id);

        Log::info('line.webhook.job.dispatch.done', ['id' => $record->id]);
    }
}
