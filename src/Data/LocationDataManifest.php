<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use InvalidArgumentException;

final class LocationDataManifest
{
    public const MANIFEST_FILE = 'manifest.json';

    /**
     * @return array<string, string>
     */
    public static function datasetFiles(): array
    {
        return [
            'provinces' => 'provinces.json',
            'counties' => 'counties.json',
            'official_districts' => 'official_districts.json',
            'rural_districts' => 'rural_districts.json',
            'cities' => 'cities.json',
            'city_regions' => 'city_regions.json',
            'city_areas' => 'city_areas.json',
            'neighborhoods' => 'neighborhoods.json',
            'neighborhood_region' => 'neighborhood_region.json',
            'aliases' => 'aliases.json',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function datasets(): array
    {
        return array_keys(self::datasetFiles());
    }

    public static function fileFor(string $dataset): string
    {
        $files = self::datasetFiles();

        if (! array_key_exists($dataset, $files)) {
            throw new InvalidArgumentException("Unknown Iran Locations dataset [{$dataset}].");
        }

        return $files[$dataset];
    }
}
