<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;

class DoctorCommand extends Command
{
    protected $signature = 'iran-locations:doctor';

    protected $description = 'Inspect Laravel Iran Locations package wiring.';

    public function handle(LocationDataValidator $validator, LocationDatabaseInspector $database): int
    {
        $this->info('Laravel Iran Locations doctor');

        $normalizer = app(LocationNormalizer::class);
        $result = $validator->validate();
        $models = $database->configuredModelsExist();
        $missingTables = $database->missingDatasetTables(null, includeDataVersion: true);

        $this->line('Normalizer contract: '.get_debug_type($normalizer));
        $this->line('Normalization driver: '.(string) config('iran-locations.normalization.driver', 'persian-core'));
        $this->line('Configuration loaded: '.(config('iran-locations.tables.provinces') ? 'yes' : 'no'));
        $this->line('Package data validation: '.($result['ok'] ? 'passed' : 'failed'));
        $this->line('Configured models: '.($this->allTrue($models) ? 'passed' : 'failed'));
        $this->line('Database tables: '.($missingTables === [] ? 'ready' : 'missing '.implode(', ', $missingTables)));
        $this->line('Latest applied database data version: '.($database->latestAppliedVersion() ?? 'none'));

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        foreach ($models as $key => $exists) {
            if (! $exists) {
                $this->error("Configured model [{$key}] does not exist or is not an Eloquent model.");
            }
        }

        $this->line('No database records were inspected or modified.');

        return $result['ok'] && $this->allTrue($models) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, bool>  $values
     */
    private function allTrue(array $values): bool
    {
        return ! in_array(false, $values, true);
    }
}
