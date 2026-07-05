<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Commands;

use Illuminate\Console\Command;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\IranLocationsManager;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;

class StatusCommand extends Command
{
    protected $signature = 'iran-locations:status';

    protected $description = 'Show Laravel Iran Locations package status.';

    public function handle(IranLocationsManager $locations, LocationDatabaseInspector $database): int
    {
        $this->info('Laravel Iran Locations status');
        $this->line('Package data status');
        $this->line('Driver: '.$this->storageDriver());
        $this->line('Mode: '.($this->storageDriver() === 'json' ? 'read-only' : 'database'));
        $this->line('Data version: '.$locations->dataVersion());

        foreach (LocationDataManifest::datasets() as $dataset) {
            $this->line("{$dataset}: ".$locations->dataCount($dataset));
        }

        if ($this->storageDriver() === 'json') {
            $this->newLine();
            $this->line('Database tables: skipped');
            $this->line('Migration/sync required: no');
            $this->line('Admin routes enabled: no');
            $this->line('API routes enabled: '.($this->enabled('iran-locations.api.enabled') ? 'yes' : 'no'));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Database status');

        $missing = $database->missingDatasetTables(null, includeDataVersion: true);

        if ($missing !== []) {
            $this->line('Database tables: missing '.implode(', ', $missing));
        } else {
            $this->line('Database tables: ready');
        }

        $databaseCounts = $database->datasetCounts();

        foreach ($databaseCounts as $dataset => $count) {
            $this->line("database {$dataset}: ".($count === null ? 'missing' : (string) $count));
        }

        $packageActiveCounts = $database->packageActiveDatasetCounts();

        foreach ($packageActiveCounts as $dataset => $count) {
            $this->line("database package active {$dataset}: ".($count === null ? 'missing' : (string) $count));
        }

        $latest = $database->latestAppliedVersion();
        $countsMatch = $this->countsMatch($locations, $packageActiveCounts);

        $this->line('Latest applied database data version: '.($latest ?? 'none'));
        $this->line('Database appears synced: '.($latest === $locations->dataVersion() && $countsMatch ? 'yes' : 'no'));
        $this->line('Admin routes enabled: '.($this->enabled('iran-locations.admin.enabled') ? 'yes' : 'no'));
        $this->line('API routes enabled: '.($this->enabled('iran-locations.api.enabled') ? 'yes' : 'no'));

        return self::SUCCESS;
    }

    private function enabled(string $key): bool
    {
        return (bool) config($key, false);
    }

    private function storageDriver(): string
    {
        return strtolower((string) config('iran-locations.storage.driver', 'database'));
    }

    /**
     * @param  array<string, int|null>  $databaseCounts
     */
    private function countsMatch(IranLocationsManager $locations, array $databaseCounts): bool
    {
        $manifest = $locations->dataManifest();
        $contains = $manifest['contains'] ?? [];

        foreach ($this->authoritativeDatasets($contains) as $dataset) {
            if (($databaseCounts[$dataset] ?? null) !== $locations->dataCount($dataset)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function authoritativeDatasets(mixed $contains): array
    {
        if (! is_array($contains)) {
            return LocationDataManifest::datasets();
        }

        return array_values(array_filter(
            LocationDataManifest::datasets(),
            static fn (string $dataset): bool => ($contains[$dataset] ?? false) === true,
        ));
    }
}
