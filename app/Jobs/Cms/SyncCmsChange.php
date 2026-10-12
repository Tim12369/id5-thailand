<?php

namespace App\Jobs\Cms;

use App\Services\Cms\CmsSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Applies one WordPress change to this application's content tables.
 *
 * Queued rather than inline so that an editor pressing Publish gets an instant
 * response, and so a transient network problem retries by itself instead of
 * losing the change. If every retry fails, the scheduled `cms:pull` reconcile
 * picks the change up on its next run — no edit is ever silently lost.
 */
class SyncCmsChange implements ShouldQueue
{
    use Queueable;

    /**
     * Which content types each WordPress taxonomy groups. For a `terms.updated`
     * event the taxonomy name travels in `$postType`.
     */
    private const TYPES_BY_TAXONOMY = [
        'category'             => ['post'],
        'post_tag'             => ['post'],
        'id5_content_category' => ['id5_announcement', 'id5_alert', 'id5_knowledge', 'id5_faq'],
        'id5_form_category'    => ['id5_form'],
        'id5_org_section'      => ['id5_person'],
        'id5_ita_section'      => ['id5_ita_card'],
        'id5_region'           => ['id5_office'],
        'id5_province'         => ['id5_office'],
    ];

    /** Three attempts over roughly a minute, then leave it to the reconcile. */
    public int $tries = 3;

    public array $backoff = [10, 30];

    public int $timeout = 120;

    public function __construct(
        public readonly string $event,
        public readonly ?string $postType = null,
        public readonly ?int $postId = null,
    ) {}

    /**
     * Never let two syncs of the same target run at once: they would race on
     * the same row, and the loser's write could resurrect stale content.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->lockKey()))->expireAfter(180)];
    }

    public function handle(): void
    {
        $sync = CmsSyncService::make();

        match (true) {
            $this->event === 'post.deleted' => $sync->deletePost($this->postType, $this->postId),
            str_starts_with($this->event, 'post.') => $sync->syncPost($this->postType, $this->postId),
            $this->event === 'settings.updated' => $this->wrap(fn () => $sync->syncSettings()),
            $this->event === 'menus.updated' => $this->wrap(fn () => $sync->syncMenus()),
            $this->event === 'texts.updated' => $this->wrap(fn () => $sync->syncTexts()),
            // A renamed or re-ordered category changes how existing content is
            // grouped, so the content types that use that category are pulled
            // again — only those, not the whole site.
            $this->event === 'terms.updated' => $this->wrap(function () use ($sync) {
                $sync->syncTerms();

                return $sync->syncAll(self::TYPES_BY_TAXONOMY[$this->postType] ?? []);
            }),
            default => null,
        };
    }

    public function failed(Throwable $e): void
    {
        Log::channel('cms')->error('CMS sync job failed; the scheduled reconcile will retry.', [
            'event'     => $this->event,
            'post_type' => $this->postType,
            'post_id'   => $this->postId,
            'error'     => $e->getMessage(),
        ]);
    }

    private function wrap(callable $callback): void
    {
        $callback();
    }

    private function lockKey(): string
    {
        return 'cms-sync:'.$this->event.':'.($this->postType ?? '-').':'.($this->postId ?? '-');
    }
}
