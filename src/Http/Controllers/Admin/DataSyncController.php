<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Http\Requests\Admin\DataSyncRequest;
use Zarbin\IranLocations\Sync\LocationSyncException;
use Zarbin\IranLocations\Sync\LocationSyncOptions;
use Zarbin\IranLocations\Sync\LocationSyncService;

class DataSyncController extends AdminController
{
    public function sync(DataSyncRequest $request, LocationSyncService $sync): RedirectResponse
    {
        try {
            $result = $sync->sync(LocationSyncOptions::make(
                dryRun: (bool) $request->boolean('dry_run'),
            ));
        } catch (LocationSyncException $exception) {
            return back()->withErrors(['sync' => $exception->getMessage()]);
        }

        return redirect()
            ->route('iran-locations.admin.data.index')
            ->with('status', $result->dryRun ? 'Dry-run sync completed. No database changes were made.' : 'Data sync completed.')
            ->with('iran_locations_sync_result', $result->summary());
    }
}
