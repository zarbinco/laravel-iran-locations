<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\IranLocationsManager;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;

class DashboardController extends AdminController
{
    public function index(IranLocationsManager $locations, LocationDatabaseInspector $database): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('dashboard', [
            'manifest' => $locations->dataManifest(),
            'datasets' => LocationDataManifest::datasets(),
            'databaseCounts' => $database->datasetCounts(),
            'packageActiveCounts' => $database->packageActiveDatasetCounts(),
            'latestAppliedVersion' => $database->latestAppliedVersion(),
            'dataVersion' => $locations->dataVersion(),
        ]);
    }
}
