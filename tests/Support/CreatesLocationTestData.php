<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Support;

use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;

trait CreatesLocationTestData
{
    protected function bindFakeLocationNormalizer(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeLocationNormalizer());
    }

    /**
     * @return array<string, Province|City|CityRegion|CityArea|Neighborhood>
     */
    protected function createLocationGraph(string $suffix = 'main'): array
    {
        $province = new Province([
            'code' => 'province-'.$suffix,
            'name_fa' => 'Province '.$suffix,
        ]);
        $province->save();

        $city = new City([
            'province_id' => $province->getKey(),
            'code' => 'city-'.$suffix,
            'name_fa' => 'City '.$suffix,
            'is_province_capital' => true,
        ]);
        $city->save();

        $region = new CityRegion([
            'city_id' => $city->getKey(),
            'code' => 'region-'.$suffix,
            'number' => 3,
            'name_fa' => 'Region '.$suffix,
            'type' => 'municipal_region',
        ]);
        $region->save();

        $area = new CityArea([
            'city_region_id' => $region->getKey(),
            'code' => 'area-'.$suffix,
            'number' => 7,
            'name_fa' => 'Area '.$suffix,
        ]);
        $area->save();

        $neighborhood = new Neighborhood([
            'city_id' => $city->getKey(),
            'default_city_region_id' => $region->getKey(),
            'default_city_area_id' => $area->getKey(),
            'code' => 'neighborhood-'.$suffix,
            'name_fa' => 'Neighborhood '.$suffix,
            'type' => 'neighborhood',
        ]);
        $neighborhood->save();

        $neighborhood->regions()->attach($region->getKey(), [
            'is_primary' => true,
            'source' => 'package',
        ]);

        return [
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'area' => $area,
            'neighborhood' => $neighborhood,
        ];
    }

    private function fakeLocationNormalizer(): LocationNormalizer
    {
        return new class implements LocationNormalizer
        {
            public function display(string $value): string
            {
                return 'display:'.$value;
            }

            public function search(string $value): string
            {
                return 'normalized:'.$value;
            }

            public function slug(string $value): string
            {
                return 'slug:'.$value;
            }
        };
    }
}
