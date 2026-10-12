<?php

namespace App\Console\Commands;

use App\Services\Cms\WordPressClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Answers "is the website still in step with WordPress?" without anyone having
 * to read a log file or open phpMyAdmin.
 */
class CmsStatus extends Command
{
    protected $signature = 'cms:status';

    protected $description = 'Show the WordPress connection and the last content sync for each type';

    public function handle(): int
    {
        $client = WordPressClient::fromConfig();

        $this->components->twoColumnDetail('Integration', config('cms.enabled') ? '<fg=green>enabled</>' : '<fg=yellow>disabled</>');
        $this->components->twoColumnDetail('WordPress URL', config('cms.base_url') ?: '<fg=red>not set</>');
        $this->components->twoColumnDetail('Credentials', $client->isConfigured() ? '<fg=green>present</>' : '<fg=red>missing</>');
        $this->components->twoColumnDetail('Webhook secret', config('cms.webhook.secret') ? '<fg=green>present</>' : '<fg=red>missing</>');

        if ($client->isConfigured()) {
            $this->components->twoColumnDetail(
                'Reachable',
                $client->health() ? '<fg=green>yes</>' : '<fg=red>no</>'
            );
        }

        $this->newLine();

        $rows = DB::table('cms_sync_state')
            ->whereIn('kind', ['pull', 'push'])
            ->orderBy('scope')
            ->get();

        if ($rows->isEmpty()) {
            $this->components->warn('No content has been synced yet. Run `php artisan cms:pull`.');

            return self::SUCCESS;
        }

        $this->table(
            ['Scope', 'Last run', 'Status', 'Detail'],
            $rows->map(fn ($row) => [
                $row->scope,
                $row->last_run_at,
                $row->status,
                (string) $row->message,
            ])->all()
        );

        $mirrored = DB::table('cms_sync_state')->where('kind', 'media')->count();
        $this->components->twoColumnDetail('Files uploaded to WordPress', (string) $mirrored);

        return self::SUCCESS;
    }
}
