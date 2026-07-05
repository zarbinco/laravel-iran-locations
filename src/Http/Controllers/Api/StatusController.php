<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\IranLocationsManager;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;

class StatusController extends Controller
{
    public function __invoke(IranLocationsManager $locations, LocationDatabaseInspector $database): JsonResponse
    {
        $manifest = $locations->dataManifest();

        if (strtolower((string) config('iran-locations.storage.driver', 'database')) === 'json') {
            return response()->json([
                'driver' => 'json',
                'mode' => 'read-only',
                'data_version' => $locations->dataVersion(),
                'manifest' => [
                    'checksum' => $manifest['checksum'] ?? null,
                    'counts' => $manifest['counts'] ?? [],
                    'contains' => $manifest['contains'] ?? [],
                ],
                'database' => [
                    'tables' => 'skipped',
                    'sync_required' => false,
                ],
            ]);
        }

        $databasePackageCounts = $database->packageActiveDatasetCounts();
        $latestAppliedVersion = $database->latestAppliedVersion();

        return response()->json([
            'data_version' => $locations->dataVersion(),
            'manifest' => [
                'checksum' => $manifest['checksum'] ?? null,
                'counts' => $manifest['counts'] ?? [],
                'contains' => $manifest['contains'] ?? [],
            ],
            'database' => [
                'counts' => $database->datasetCounts(),
                'package_active_counts' => $databasePackageCounts,
                'latest_applied_version' => $latestAppliedVersion,
                'synced' => $latestAppliedVersion === $locations->dataVersion()
                    && $this->countsMatch($manifest, $databasePackageCounts),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, int|null>  $databaseCounts
     */
    private function countsMatch(array $manifest, array $databaseCounts): bool
    {
        $counts = $manifest['counts'] ?? [];
        $contains = $manifest['contains'] ?? [];

        if (! is_array($counts) || ! is_array($contains)) {
            return false;
        }

        foreach ($counts as $dataset => $count) {
            if (($contains[$dataset] ?? false) !== true) {
                continue;
            }

            if (($databaseCounts[$dataset] ?? null) !== $count) {
                return false;
            }
        }

        return true;
    }
}
