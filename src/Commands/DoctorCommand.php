<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataValidator;

class DoctorCommand extends Command
{
    protected $signature = 'iran-locations:doctor';

    protected $description = 'Inspect Laravel Iran Locations package wiring.';

    public function handle(LocationDataValidator $validator): int
    {
        $this->info('Laravel Iran Locations doctor');

        $normalizer = app(LocationNormalizer::class);
        $result = $validator->validate();

        $this->line('Normalizer contract: '.get_debug_type($normalizer));
        $this->line('Normalization driver: '.(string) config('iran-locations.normalization.driver', 'persian-core'));
        $this->line('Configuration loaded: '.(config('iran-locations.tables.provinces') ? 'yes' : 'no'));
        $this->line('Package data validation: '.($result['ok'] ? 'passed' : 'failed'));

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->line('No database records were inspected or modified.');

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
