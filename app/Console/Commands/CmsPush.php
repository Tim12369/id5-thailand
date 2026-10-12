<?php

namespace App\Console\Commands;

use App\Services\Cms\CmsPushService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Moves the content that already exists in this database into WordPress.
 *
 * Run once at cut-over. It is safe to run again: WordPress matches each item on
 * the database row it came from and updates that post rather than creating a
 * second one, and nothing in this database is deleted either way.
 */
class CmsPush extends Command
{
    protected $signature = 'cms:push
        {--type=* : Limit to these WordPress post types (repeatable)}
        {--dry-run : Report what would be sent without sending anything}
        {--settings : Export only the site-wide settings}
        {--texts : Export only the changed wording (menu labels, headings, buttons)}
        {--terms : Export only the categories WordPress does not have yet}';

    protected $description = 'Export existing website content into WordPress (one-time migration)';

    public function handle(): int
    {
        if (! config('cms.enabled')) {
            $this->components->error('CMS integration is disabled. Set CMS_ENABLED=true to use this command.');

            return self::FAILURE;
        }

        $push = CmsPushService::make()->dryRun((bool) $this->option('dry-run'));

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run — nothing will be written to WordPress.');
        }

        try {
            if ($this->option('terms')) {
                $count = $push->pushTerms();
                $this->components->info("Exported {$count} categories.");

                return self::SUCCESS;
            }

            if ($this->option('texts')) {
                $count = $push->pushTexts();
                $this->components->info("Exported {$count} changed lines of wording.");

                return self::SUCCESS;
            }

            if ($this->option('settings')) {
                $count = $push->pushSettings();
                $this->components->info("Exported {$count} settings.");

                return self::SUCCESS;
            }

            $types = array_filter((array) $this->option('type')) ?: CmsPushService::ORDER;
            $rows  = [];

            foreach ($types as $postType) {
                if (! in_array($postType, CmsPushService::ORDER, true)) {
                    $this->components->warn("Unknown content type '{$postType}' — skipped.");

                    continue;
                }

                $this->components->task(
                    "Exporting {$postType}",
                    function () use ($push, $postType, &$rows) {
                        $result = $push->push($postType);

                        $rows[] = [
                            $postType,
                            $result['created'],
                            $result['updated'],
                            $result['skipped'],
                            $result['failed'],
                        ];

                        return $result['failed'] === 0;
                    }
                );
            }

            if (! $this->option('type')) {
                // Site-wide settings need a WordPress account that may change
                // them (`edit_theme_options`). The everyday sync account is
                // deliberately an Editor, which may not — so a refusal here is
                // reported and skipped rather than failing an export whose
                // content has already gone across.
                try {
                    $settings = $push->pushSettings();
                    $rows[]   = ['(settings)', 0, $settings, 0, 0];
                } catch (Throwable $e) {
                    $rows[] = ['(settings)', 0, 0, 1, 0];
                    $settingsNote = 'Site-wide settings were not exported: the WordPress sync account is not allowed to change them. '
                        .'Set them in WordPress (แก้ไขเว็บไซต์ → ข้อความทั่วทั้งเว็บไซต์), or run this once with an administrator application password.';
                }
            }
        } catch (Throwable $e) {
            $this->components->error('Export failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Content type', 'Created', 'Updated', 'Skipped', 'Failed'], $rows);

        if (isset($settingsNote)) {
            $this->components->warn($settingsNote);
        }

        $failed = array_sum(array_column($rows, 4));

        if ($failed > 0) {
            $this->components->warn("{$failed} item(s) failed — see storage/logs/cms.log.");

            return self::FAILURE;
        }

        if (! $this->option('dry-run')) {
            $this->components->info('Export complete. Run `php artisan cms:pull` to confirm the round trip.');
        }

        return self::SUCCESS;
    }
}
