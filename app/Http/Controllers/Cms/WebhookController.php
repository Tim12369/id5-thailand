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

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event'     => ['required', 'string', 'max:50'],
            'post_type' => ['nullable', 'string', 'max:50'],
            'post_id'   => ['nullable', 'integer', 'min:1'],
            'taxonomy'  => ['nullable', 'string', 'max:50'],
        ]);

        if (! in_array($data['event'], self::EVENTS, true)) {
            return response()->json(['status' => 'ignored', 'reason' => 'unknown event']);
        }

        // `nullable` rules omit the key entirely when it was not sent, so these
        // are read defensively rather than by direct index.
        $postType = $data['post_type'] ?? null;
        $postId   = $data['post_id'] ?? null;

        if (str_starts_with($data['event'], 'post.')) {
            if (blank($postType) || blank($postId)) {
                return response()->json(['status' => 'ignored', 'reason' => 'incomplete event'], 422);
            }

            // Only post types this application actually maps are queued, so a
            // notification about an unrelated WordPress post does nothing.
            if (! config("cms.mappers.{$postType}")) {
                return response()->json(['status' => 'ignored', 'reason' => 'unmapped type']);
            }
        }

        // For a category change, carry the taxonomy name in the type slot so
        // only the content that uses it is refreshed.
        if ($data['event'] === 'terms.updated') {
            $postType = $data['taxonomy'] ?? null;
        }

        Log::channel('cms')->info('Webhook accepted.', [
            'event'     => $data['event'],
            'post_type' => $postType,
            'post_id'   => $postId,
        ]);

        if (config('cms.sync.mode') === 'queue') {
            SyncCmsChange::dispatch($data['event'], $postType, $postId);

            return $this->acknowledge('queued');
        }

        // Default: apply the change in this same process, right after the reply
        // below has been sent. WordPress is answered at once (an editor never
        // waits on the sync), and the website updates without a separate queue
        // worker having to be kept alive — one less thing that can stop
        // silently and leave edits not appearing.
        app()->terminating(function () use ($data, $postType, $postId) {
            try {
                (new SyncCmsChange($data['event'], $postType, $postId))->handle();
            } catch (Throwable $e) {
                Log::channel('cms')->warning('Immediate sync failed; queued for retry (the scheduled reconcile also covers it).', [
                    'event'     => $data['event'],
                    'post_type' => $postType,
                    'post_id'   => $postId,
                    'error'     => $e->getMessage(),
                ]);

                // If a queue worker is running it retries; if not, the hourly
                // `cms:pull` picks the change up. Either way nothing is lost.
                rescue(fn () => SyncCmsChange::dispatch($data['event'], $postType, $postId)->delay(30), report: false);
            }
        });

        return $this->acknowledge('accepted');
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
