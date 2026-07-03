<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Contracts\LocationNormalizer;

class DoctorCommand extends Command
{
    protected $signature = 'iran-locations:doctor';

    protected $description = 'Inspect Laravel Iran Locations package wiring.';

    public function handle(): int
    {
        $this->info('Laravel Iran Locations doctor');

        $normalizer = app(LocationNormalizer::class);

        $this->line('Normalizer contract: '.get_debug_type($normalizer));
        $this->line('Normalization driver: '.(string) config('iran-locations.normalization.driver', 'persian-core'));
        $this->line('Configuration loaded: '.(config('iran-locations.tables.provinces') ? 'yes' : 'no'));
        $this->line('No database records were inspected or modified.');

        return self::SUCCESS;
    }
}
