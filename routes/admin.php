<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zarbin\IranLocations\Http\Controllers\Admin\CityAreaController;
use Zarbin\IranLocations\Http\Controllers\Admin\CityController;
use Zarbin\IranLocations\Http\Controllers\Admin\CityRegionController;
use Zarbin\IranLocations\Http\Controllers\Admin\DashboardController;
use Zarbin\IranLocations\Http\Controllers\Admin\DataStatusController;
use Zarbin\IranLocations\Http\Controllers\Admin\DataSyncController;
use Zarbin\IranLocations\Http\Controllers\Admin\LocationAliasController;
use Zarbin\IranLocations\Http\Controllers\Admin\NeighborhoodController;
use Zarbin\IranLocations\Http\Controllers\Admin\ProvinceController;

Route::name('iran-locations.admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('data', [DataStatusController::class, 'index'])->name('data.index');
    Route::post('data/sync', [DataSyncController::class, 'sync'])->name('data.sync');

    Route::resource('provinces', ProvinceController::class)->except(['show']);
    Route::resource('cities', CityController::class)->except(['show']);
    Route::resource('city-regions', CityRegionController::class)->except(['show'])
        ->parameters(['city-regions' => 'city_region']);
    Route::resource('city-areas', CityAreaController::class)->except(['show'])
        ->parameters(['city-areas' => 'city_area']);
    Route::resource('neighborhoods', NeighborhoodController::class)->except(['show']);
    Route::resource('aliases', LocationAliasController::class)->except(['show']);
});
