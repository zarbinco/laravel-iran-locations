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
        $this->line('Package data status');
        $this->line('Data version: '.$locations->dataVersion());

        foreach (['provinces', 'cities', 'city_regions', 'city_areas', 'neighborhoods', 'aliases'] as $dataset) {
            $this->line("{$dataset}: ".$locations->dataCount($dataset));
        }

        $this->line('Database sync status: not implemented.');
        $this->line('Admin routes enabled: '.($this->enabled('iran-locations.admin.enabled') ? 'yes' : 'no'));
        $this->line('API routes enabled: '.($this->enabled('iran-locations.api.enabled') ? 'yes' : 'no'));

        return self::SUCCESS;
    }

    private function enabled(string $key): bool
    {
        return (bool) config($key, false);
    }
}
