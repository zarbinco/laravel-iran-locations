<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Support;

use InvalidArgumentException;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;

final class LocationModelResolver
{
    /**
     * @return array<int, string>
     */
    public static function locationTypeKeys(): array
    {
        return array_keys(self::locationTypeDatasets());
    }

    /**
     * @return array<string, class-string>
     */
    public static function morphMap(): array
    {
        $map = [];

        foreach (self::locationTypeKeys() as $key) {
            $map[$key] = self::model($key);
        }

        return $map;
    }

    public static function normalizeLocationType(string $type): string
    {
        $normalized = strtolower(trim($type));
        $normalized = str_replace('-', '_', $normalized);

        $aliases = [
            'province' => 'province',
            'provinces' => 'province',
            'county' => 'county',
            'counties' => 'county',
            'official_district' => 'official_district',
            'official_districts' => 'official_district',
            'rural_district' => 'rural_district',
            'rural_districts' => 'rural_district',
            'city' => 'city',
            'cities' => 'city',
            'city_region' => 'city_region',
            'city_regions' => 'city_region',
            'city_area' => 'city_area',
            'city_areas' => 'city_area',
            'neighborhood' => 'neighborhood',
            'neighborhoods' => 'neighborhood',
        ];

        $key = $aliases[$normalized] ?? null;

        if ($key === null) {
            throw new InvalidArgumentException("Unsupported Iran Locations location type [{$type}].");
        }

        return $key;
    }

    /**
     * @return class-string
     */
    public static function modelForLocationType(string $type): string
    {
        return self::model(self::normalizeLocationType($type));
    }

    public static function locationTypeForModel(string|object $model): string
    {
        $class = is_object($model) ? $model::class : $model;

        foreach (self::morphMap() as $key => $configuredModel) {
            if ($class === $configuredModel || is_a($class, $configuredModel, true)) {
                return $key;
            }
        }

        throw new InvalidArgumentException("Unsupported Iran Locations location model [{$class}].");
    }

    public static function datasetForLocationType(string $type): string
    {
        $key = self::normalizeLocationType($type);

        return self::locationTypeDatasets()[$key];
    }

    /**
     * @return array<string, string>
     */
    public static function locationTypeLabels(): array
    {
        return [
            'province' => 'Province',
            'county' => 'County',
            'official_district' => 'Official district',
            'rural_district' => 'Rural district',
            'city' => 'City',
            'city_region' => 'City region',
            'city_area' => 'City area',
            'neighborhood' => 'Neighborhood',
        ];
    }

    public static function model(string $key): string
    {
        $model = config("iran-locations.models.{$key}");

        if (is_string($model) && $model !== '') {
            return $model;
        }

        $default = self::defaultModels()[$key] ?? null;

        if ($default === null) {
            throw new InvalidArgumentException("Unknown Iran Locations model key [{$key}].");
        }

        return $default;
    }

    public static function table(string $key): string
    {
        $direct = self::configuredTable($key);
        $legacyKey = self::legacyTableKey($key);
        $legacy = $legacyKey === null ? null : self::configuredTable($legacyKey);
        $defaults = self::defaultTables();

        if ($direct !== null && $direct !== ($defaults[$key] ?? null)) {
            return $direct;
        }

        if ($legacy !== null && $legacy !== ($defaults[$legacyKey] ?? null)) {
            return $legacy;
        }

        if ($direct !== null) {
            return $direct;
        }

        if ($legacy !== null) {
            return $legacy;
        }

        $default = $defaults[$key] ?? null;

        if ($default === null) {
            throw new InvalidArgumentException("Unknown Iran Locations table key [{$key}].");
        }

        return $default;
    }

    private static function configuredTable(string $key): ?string
    {
        $table = config("iran-locations.tables.{$key}");

        return is_string($table) && $table !== '' ? $table : null;
    }

    private static function legacyTableKey(string $key): ?string
    {
        return [
            'province' => 'provinces',
            'county' => 'counties',
            'official_district' => 'official_districts',
            'rural_district' => 'rural_districts',
            'city' => 'cities',
            'city_region' => 'city_regions',
            'city_area' => 'city_areas',
            'neighborhood' => 'neighborhoods',
            'location_alias' => 'location_aliases',
            'data_version' => 'data_versions',
        ][$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private static function locationTypeDatasets(): array
    {
        return [
            'province' => 'provinces',
            'county' => 'counties',
            'official_district' => 'official_districts',
            'rural_district' => 'rural_districts',
            'city' => 'cities',
            'city_region' => 'city_regions',
            'city_area' => 'city_areas',
            'neighborhood' => 'neighborhoods',
        ];
    }

    /**
     * @return array<string, class-string>
     */
    private static function defaultModels(): array
    {
        return [
            'province' => Province::class,
            'county' => County::class,
            'official_district' => OfficialDistrict::class,
            'rural_district' => RuralDistrict::class,
            'city' => City::class,
            'city_region' => CityRegion::class,
            'city_area' => CityArea::class,
            'neighborhood' => Neighborhood::class,
            'location_alias' => LocationAlias::class,
            'data_version' => LocationDataVersion::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function defaultTables(): array
    {
        return [
            'province' => 'iran_provinces',
            'provinces' => 'iran_provinces',
            'county' => 'iran_counties',
            'counties' => 'iran_counties',
            'official_district' => 'iran_official_districts',
            'official_districts' => 'iran_official_districts',
            'rural_district' => 'iran_rural_districts',
            'rural_districts' => 'iran_rural_districts',
            'city' => 'iran_cities',
            'cities' => 'iran_cities',
            'city_region' => 'iran_city_regions',
            'city_regions' => 'iran_city_regions',
            'city_area' => 'iran_city_areas',
            'city_areas' => 'iran_city_areas',
            'neighborhood' => 'iran_neighborhoods',
            'neighborhoods' => 'iran_neighborhoods',
            'neighborhood_region' => 'iran_neighborhood_region',
            'location_alias' => 'iran_location_aliases',
            'location_aliases' => 'iran_location_aliases',
            'data_version' => 'iran_location_data_versions',
            'data_versions' => 'iran_location_data_versions',
        ];
    }
}
