<?php

declare(strict_types=1);

use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;

return [
    'tables' => [
        'provinces' => 'iran_provinces',
        'cities' => 'iran_cities',
        'city_regions' => 'iran_city_regions',
        'city_areas' => 'iran_city_areas',
        'neighborhoods' => 'iran_neighborhoods',
        'neighborhood_region' => 'iran_neighborhood_region',
        'location_aliases' => 'iran_location_aliases',
        'data_versions' => 'iran_location_data_versions',
    ],

    'models' => [
        'province' => Province::class,
        'city' => City::class,
        'city_region' => CityRegion::class,
        'city_area' => CityArea::class,
        'neighborhood' => Neighborhood::class,
        'location_alias' => LocationAlias::class,
        'data_version' => LocationDataVersion::class,
    ],

    'route_key' => 'id',

    'normalization' => [
        'driver' => 'persian-core',
        'on_save' => true,
        'on_sync' => true,
        'aliases' => true,
        'slugs' => true,
    ],

    'data' => [
        'current_version' => '0.1.0-dev',
        'preserve_custom_records' => true,
        'package_record_delete_behavior' => 'deprecate',
        'allow_package_record_direct_edit' => false,
    ],

    'admin' => [
        'enabled' => false,
        'prefix' => 'admin/iran-locations',
        'middleware' => ['web', 'auth'],
        'gate' => null,
        'per_page' => 25,
        'tailwind' => true,
    ],

    'api' => [
        'enabled' => false,
        'prefix' => 'iran-locations/api',
        'middleware' => ['web'],
    ],

    'search' => [
        'min_length' => 2,
        'limit' => 20,
        'include_aliases' => true,
    ],

    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 100,
    ],
];
