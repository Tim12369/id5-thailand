<?php

namespace App\Console\Commands;

use App\Services\Cms\MediaVault;
use Illuminate\Console\Command;

/**
 * Puts the pictures mirrored from WordPress back on the disk after a restart.
 *
 * Run by the container's start-up script. On a host with a lasting disk
 * (CMS_MEDIA_PERSIST off) it has nothing to do and says so.
 */
class CmsMediaRestore extends Command
{
    protected $signature = 'cms:media:restore';

    protected $description = 'Restore the files mirrored from WordPress from their database copies';

    public function handle(): int
    {
        $vault = MediaVault::fromConfig();

        if (! $vault->enabled()) {
            $this->components->info('Database copies of mirrored files are switched off (CMS_MEDIA_PERSIST); nothing to restore.');

            return self::SUCCESS;
        }

        $count = $vault->restoreAll();

        $this->components->info(sprintf(
            '%d file(s) restored, %d already in place%s.',
            $count['restored'],
            $count['present'],
            $count['failed'] ? ', '.$count['failed'].' could not be restored' : ''
        ));

        return $count['failed'] ? self::FAILURE : self::SUCCESS;
    }
}
