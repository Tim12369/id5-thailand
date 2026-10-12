<?php

namespace App\Console\Commands;

use App\Services\Cms\CmsSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reconciles this application's content with WordPress.
 *
 * Webhooks keep the site current second to second; this is the safety net that
 * catches anything a webhook missed — a delivery that failed while the site was
 * deploying, an edit made while the queue worker was stopped, a change applied
 * directly in the WordPress database. Running it is always safe: every write is
 * keyed on the WordPress post ID, so a reconcile either changes nothing or
 * repairs a difference.
 */
class CmsPull extends Command
{
    protected $signature = 'cms:pull
        {--type=* : Limit to these WordPress post types (repeatable)}
        {--settings : Pull only the site-wide settings}
        {--menus : Pull only the navigation menus}
        {--texts : Pull only the wording (menu labels, headings, buttons)}
        {--retire-unmanaged : After a full pull, retire news, FAQs, people and ITA topics that no WordPress item owns (a database seeded separately)}';

    protected $description = 'Pull content from WordPress into the website database';

    public function handle(): int
    {
        if (! config('cms.enabled')) {
            $this->components->error('CMS integration is disabled. Set CMS_ENABLED=true to use this command.');

            return self::FAILURE;
        }

        $sync = CmsSyncService::make();

        if (! $sync->client()->isConfigured()) {
            $this->components->error('WordPress connection is not configured — see docs/wordpress-setup.md.');

            return self::FAILURE;
        }

        try {
            if ($this->option('settings')) {
                $count = $sync->syncSettings();
                $this->components->info("Pulled {$count} settings.");

                return self::SUCCESS;
            }

            if ($this->option('texts')) {
                $count = $sync->syncTexts();
                $this->components->info("Pulled {$count} changed lines of wording.");

                return self::SUCCESS;
            }

            if ($this->option('menus')) {
                $count = $sync->syncMenus();
                $this->components->info("Pulled {$count} menu items.");

                return self::SUCCESS;
            }

            $types   = array_filter((array) $this->option('type'));
            $summary = $sync->syncAll($types);
        } catch (Throwable $e) {
            $this->components->error('Pull failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Content type', 'Synced', 'Skipped', 'Failed'],
            collect($summary)->map(fn (array $row, string $type) => [
                $type,
                $row['synced'],
                $row['skipped'],
                $row['failed'],
            ])->values()->all()
        );

        $failed = collect($summary)->sum('failed');

        if (! $types && ($this->option('retire-unmanaged') || config('cms.sync.retire_unmanaged'))) {
            foreach ($sync->retireUnmanaged($summary) as $kind => $count) {
                $this->components->twoColumnDetail("Retired (predates WordPress): {$kind}", (string) $count);
            }
        }

        if ($failed > 0) {
            $this->components->warn("{$failed} item(s) could not be synced — see storage/logs/cms.log.");

            return self::FAILURE;
        }

        $this->components->info('Content is up to date.');

        return self::SUCCESS;
    }
}
