<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zarbin\IranLocations\Http\Controllers\Api\CityAreaController;
use Zarbin\IranLocations\Http\Controllers\Api\CityController;
use Zarbin\IranLocations\Http\Controllers\Api\CityRegionController;
use Zarbin\IranLocations\Http\Controllers\Api\LocationAliasController;
use Zarbin\IranLocations\Http\Controllers\Api\NeighborhoodController;
use Zarbin\IranLocations\Http\Controllers\Api\OptionController;
use Zarbin\IranLocations\Http\Controllers\Api\ProvinceController;
use Zarbin\IranLocations\Http\Controllers\Api\SearchController;
use Zarbin\IranLocations\Http\Controllers\Api\StatusController;

Route::get('/status', StatusController::class)->name('iran-locations.api.status');
Route::get('/search', SearchController::class)->name('iran-locations.api.search');

Route::get('/provinces', [ProvinceController::class, 'index'])->name('iran-locations.api.provinces.index');
Route::get('/provinces/{province}/cities', [ProvinceController::class, 'cities'])->name('iran-locations.api.provinces.cities');

Route::get('/cities', [CityController::class, 'index'])->name('iran-locations.api.cities.index');
Route::get('/cities/{city}/regions', [CityController::class, 'regions'])->name('iran-locations.api.cities.regions');
Route::get('/cities/{city}/areas', [CityController::class, 'areas'])->name('iran-locations.api.cities.areas');
Route::get('/cities/{city}/neighborhoods', [CityController::class, 'neighborhoods'])->name('iran-locations.api.cities.neighborhoods');

Route::get('/city-regions', [CityRegionController::class, 'index'])->name('iran-locations.api.city-regions.index');
Route::get('/city-regions/{region}/areas', [CityRegionController::class, 'areas'])->name('iran-locations.api.city-regions.areas');
Route::get('/city-regions/{region}/neighborhoods', [CityRegionController::class, 'neighborhoods'])->name('iran-locations.api.city-regions.neighborhoods');

Route::get('/city-areas', [CityAreaController::class, 'index'])->name('iran-locations.api.city-areas.index');
Route::get('/city-areas/{area}/neighborhoods', [CityAreaController::class, 'neighborhoods'])->name('iran-locations.api.city-areas.neighborhoods');

Route::get('/neighborhoods', [NeighborhoodController::class, 'index'])->name('iran-locations.api.neighborhoods.index');
Route::get('/aliases', [LocationAliasController::class, 'index'])->name('iran-locations.api.aliases.index');

Route::get('/options/provinces', [OptionController::class, 'provinces'])->name('iran-locations.api.options.provinces');
Route::get('/options/cities', [OptionController::class, 'cities'])->name('iran-locations.api.options.cities');
Route::get('/options/city-regions', [OptionController::class, 'cityRegions'])->name('iran-locations.api.options.city-regions');
Route::get('/options/city-areas', [OptionController::class, 'cityAreas'])->name('iran-locations.api.options.city-areas');
Route::get('/options/neighborhoods', [OptionController::class, 'neighborhoods'])->name('iran-locations.api.options.neighborhoods');
