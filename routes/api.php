<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zarbin\IranLocations\IranLocationsManager;

Route::get('/status', static fn (IranLocationsManager $locations): array => [
    'data_version' => $locations->dataVersion(),
])
    ->name('iran-locations.api.status');
