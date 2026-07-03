<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'iran-locations:install {--force : Pass --force when publishing package assets manually}';

    protected $description = 'Show installation guidance for Laravel Iran Locations.';

    public function handle(): int
    {
        $this->info('Laravel Iran Locations');
        $this->line('Configuration and migrations are publishable through vendor:publish tags.');
        $this->line('Available tags: iran-locations-config, iran-locations-migrations, iran-locations-views.');
        $this->line('No database records were created or modified.');

        return self::SUCCESS;
    }
}
