<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Sync\LocationSyncException;
use Zarbin\IranLocations\Sync\LocationSyncOptions;
use Zarbin\IranLocations\Sync\LocationSyncService;

class InstallCommand extends Command
{
    protected $signature = 'iran-locations:install
        {--force : Pass --force when publishing package assets manually}
        {--sync : Run a safe data sync after migrations are available}';

    protected $description = 'Show installation guidance for Laravel Iran Locations.';

    public function handle(LocationSyncService $sync): int
    {
        $this->info('Laravel Iran Locations');
        $this->line('Configuration and migrations are publishable through vendor:publish tags.');
        $this->line('Available tags: iran-locations-config, iran-locations-migrations, iran-locations-views.');

        if (! (bool) $this->option('sync')) {
            $this->line('No database records were created or modified.');
            $this->line('Next suggested commands: php artisan migrate, then php artisan iran-locations:sync --dry-run');

            return self::SUCCESS;
        }

        try {
            $result = $sync->sync(LocationSyncOptions::make(force: (bool) $this->option('force')));
        } catch (LocationSyncException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $totals = $result->totals();
        $this->line(sprintf(
            'Sync summary: +%d ~%d =%d -%d skipped %d failed %d',
            $totals['created'],
            $totals['updated'],
            $totals['unchanged'],
            $totals['deprecated'],
            $totals['skipped'],
            $totals['failed'],
        ));

        return $result->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
