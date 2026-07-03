<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\IranLocationsManager;

class StatusCommand extends Command
{
    protected $signature = 'iran-locations:status';

    protected $description = 'Show Laravel Iran Locations package status.';

    public function handle(IranLocationsManager $locations): int
    {
        $this->info('Laravel Iran Locations status');
        $this->line('Configured data version: '.$locations->dataVersion());
        $this->line('Admin routes enabled: '.($this->enabled('iran-locations.admin.enabled') ? 'yes' : 'no'));
        $this->line('API routes enabled: '.($this->enabled('iran-locations.api.enabled') ? 'yes' : 'no'));
        $this->line('Province table: '.$locations->table('provinces'));
        $this->line('City table: '.$locations->table('cities'));

        return self::SUCCESS;
    }

    private function enabled(string $key): bool
    {
        return (bool) config($key, false);
    }
}
