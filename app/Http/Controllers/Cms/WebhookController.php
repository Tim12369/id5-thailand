<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Jobs\Cms\SyncCmsChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives "something changed" notifications from the WordPress editing install.
 *
 * The request is already authenticated by VerifyCmsWebhookSignature. All this
 * does is validate the shape, answer immediately, and then apply the change —
 * an editor pressing Publish should never be left waiting on this application's
 * database, and a slow sync should never cause WordPress to retry a change
 * that is already being applied.
 */
class WebhookController extends Controller
{
    /**
     * Events this application knows how to act on. Anything else is accepted
     * and ignored, so a newer plugin sending a new event never causes an error.
     */
    private const EVENTS = [
        'post.created',
        'post.updated',
        'post.deleted',
        'settings.updated',
        'menus.updated',
        'texts.updated',
        'terms.updated',
        'site.sync',
    ];

    /** At most this many changes in one "batch" notification. */
    private const BATCH_LIMIT = 50;

    public function __invoke(Request $request): JsonResponse
    {
        // Several changes made by one action in WordPress (moving a section
        // renumbers its neighbours, too) arrive together as one "batch", so
        // they are applied one after the other by this one request.
        if ($request->input('event') === 'batch') {
            $request->validate([
                'events'   => ['required', 'array', 'min:1', 'max:'.self::BATCH_LIMIT],
                'events.*' => ['array'],
            ]);

            $changes = array_values(array_filter(array_map(
                fn ($item) => is_array($item) ? $this->accept($item) : null,
                (array) $request->input('events')
            )));
        } else {
            $one = $this->accept($request->validate([
                'event'     => ['required', 'string', 'max:50'],
                'post_type' => ['nullable', 'string', 'max:50'],
                'post_id'   => ['nullable', 'integer', 'min:1'],
                'taxonomy'  => ['nullable', 'string', 'max:50'],
            ]));

            if (is_string($one)) {
                return response()->json(['status' => 'ignored', 'reason' => $one], $one === 'incomplete event' ? 422 : 200);
            }

            $changes = [$one];
        }

        $changes = array_values(array_filter($changes, 'is_array'));

        if (! $changes) {
            return response()->json(['status' => 'ignored', 'reason' => 'nothing to apply']);
        }

        if (config('cms.sync.mode') === 'queue') {
            foreach ($changes as [$event, $postType, $postId]) {
                SyncCmsChange::dispatch($event, $postType, $postId);
            }

            return $this->acknowledge('queued');
        }

        // Default: apply the change in this same process, right after the reply
        // below has been sent. WordPress is answered at once (an editor never
        // waits on the sync), and the website updates without a separate queue
        // worker having to be kept alive — one less thing that can stop
        // silently and leave edits not appearing.
        app()->terminating(function () use ($changes) {
            foreach ($changes as [$event, $postType, $postId]) {
                try {
                    (new SyncCmsChange($event, $postType, $postId))->handle();
                } catch (Throwable $e) {
                    Log::channel('cms')->warning('Immediate sync failed; queued for retry (the scheduled reconcile also covers it).', [
                        'event'     => $event,
                        'post_type' => $postType,
                        'post_id'   => $postId,
                        'error'     => $e->getMessage(),
                    ]);

                    // If a queue worker is running it retries; if not, the hourly
                    // `cms:pull` picks the change up. Either way nothing is lost.
                    rescue(fn () => SyncCmsChange::dispatch($event, $postType, $postId)->delay(30), report: false);
                }
            }
        });

        return $this->acknowledge('accepted');
    }

    /**
     * Check one change: [event, type, id] to apply, or the reason it is
     * ignored.
     *
     * @return array{0: string, 1: ?string, 2: ?int}|string
     */
    private function accept(array $data): array|string
    {
        $event = is_string($data['event'] ?? null) ? $data['event'] : '';

        if (! in_array($event, self::EVENTS, true)) {
            return 'unknown event';
        }

        // `nullable` rules omit the key entirely when it was not sent, so these
        // are read defensively rather than by direct index.
        $postType = isset($data['post_type']) && is_string($data['post_type']) && strlen($data['post_type']) <= 50 ? $data['post_type'] : null;
        $postId   = isset($data['post_id']) && filter_var($data['post_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false ? (int) $data['post_id'] : null;

        if (str_starts_with($event, 'post.')) {
            if (blank($postType) || blank($postId)) {
                return 'incomplete event';
            }

            // Only post types this application actually maps are queued, so a
            // notification about an unrelated WordPress post does nothing.
            if (! config("cms.mappers.{$postType}")) {
                return 'unmapped type';
            }
        }

        // For a category change, carry the taxonomy name in the type slot so
        // only the content that uses it is refreshed.
        if ($event === 'terms.updated') {
            $postType = isset($data['taxonomy']) && is_string($data['taxonomy']) && strlen($data['taxonomy']) <= 50 ? $data['taxonomy'] : null;
        }

        Log::channel('cms')->info('Webhook accepted.', [
            'event'     => $event,
            'post_type' => $postType,
            'post_id'   => $postId,
        ]);

        return [$event, $postType, $postId];
    }

    /**
     * Reply with an explicit length, so the caller knows the answer is complete
     * the moment it arrives and does not wait for this process to finish the
     * work it does after responding.
     */
    private function acknowledge(string $status): JsonResponse
    {
        $response = response()->json(['status' => $status]);

        $response->headers->set('Content-Length', (string) strlen($response->getContent()));
        $response->headers->set('Connection', 'close');

        return $response;
    }
}
