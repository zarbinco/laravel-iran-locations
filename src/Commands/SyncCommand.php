<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;

class SyncCommand extends Command
{
    protected $signature = 'iran-locations:sync {--dry-run : Preview sync actions without writing data}';

    protected $description = 'Prepare Iran location data synchronization.';

    public function handle(): int
    {
        $this->info('Laravel Iran Locations sync');

        if ($this->option('dry-run')) {
            $this->line('Dry run requested.');
        }

        $this->line('Data synchronization is not implemented yet.');
        $this->line('No database records were created, updated, deleted, or deprecated.');

        return self::SUCCESS;
    }
}
