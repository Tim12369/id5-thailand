<?php

namespace App\Console\Commands\Stations;

use App\Services\Stations\StationImportService;
use Illuminate\Console\Command;

/**
 * Bulk-imports/updates immigration offices from a CSV of manually-verified
 * records (see StationImportService for the upsert-by-code rules).
 */
class ImportStationsCommand extends Command
{
    protected $signature = 'stations:import {path : Path to the CSV file}';

    protected $description = 'Import or update immigration offices from a verified CSV file (upserts by office code, never duplicates or deletes)';

    public function handle(StationImportService $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $result = $importer->importFromFile($path);

        $this->info("Created: {$result['created']}  Updated: {$result['updated']}  Skipped: {$result['skipped']}");

        if (! empty($result['errors'])) {
            $this->warn('Issues encountered:');
            foreach ($result['errors'] as $err) {
                $this->line("  Row {$err['row']}: {$err['message']}");
            }
        }

        return self::SUCCESS;
    }
}
