<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_main_relationships_work(): void
    {
        $records = $this->createLocationGraph();

        /** @var Province $province */
        $province = $records['province'];
        /** @var City $city */
        $city = $records['city'];
        /** @var CityRegion $region */
        $region = $records['region'];
        /** @var CityArea $area */
        $area = $records['area'];
        /** @var Neighborhood $neighborhood */
        $neighborhood = $records['neighborhood'];

        $neighborhood->regions()->attach($region->getKey(), [
            'is_primary' => true,
            'source' => 'package',
            'confidence' => 100,
        ]);

        self::assertTrue($province->cities()->first()->is($city));
        self::assertTrue($city->province->is($province));
        self::assertTrue($city->regions()->first()->is($region));
        self::assertTrue($city->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($region->city->is($city));
        self::assertTrue($region->areas()->first()->is($area));
        self::assertTrue($region->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($area->region->is($region));
        self::assertTrue($area->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($neighborhood->city->is($city));
        self::assertTrue($neighborhood->defaultRegion->is($region));
        self::assertTrue($neighborhood->defaultArea->is($area));
        self::assertTrue($neighborhood->regions()->first()->is($region));
    }

    public function test_alias_relationships_work_for_location_models(): void
    {
        $records = $this->createLocationGraph();

        foreach (['province', 'city', 'region', 'area', 'neighborhood'] as $key) {
            $model = $records[$key];
            $alias = $model->aliases()->create([
                'alias' => $key.' alias',
            ]);

            self::assertTrue($model->aliases()->whereKey($alias->getKey())->exists());
            self::assertSame($key.' alias', $alias->getAttribute('alias'));
            self::assertNotSame('', $alias->getAttribute('normalized_alias'));
        }
    }

    public function test_replaced_by_relationships_work_for_location_models(): void
    {
        $pairs = $this->createReplacementPairs();

        foreach ($pairs as [$original, $replacement]) {
            $original->markDeprecated($replacement)->save();

            $freshOriginal = $original->fresh();

            self::assertTrue($freshOriginal->replacedBy->is($replacement));
            self::assertTrue($replacement->replacements()->whereKey($original->getKey())->exists());
        }
    }

    /**
     * @return array<string, Province|City|CityRegion|CityArea|Neighborhood>
     */
    private function createLocationGraph(string $suffix = ''): array
    {
        $province = new Province([
            'code' => 'province'.$suffix,
            'name_fa' => 'Province '.$suffix,
        ]);
        $province->save();

        $city = new City([
            'province_id' => $province->getKey(),
            'code' => 'city'.$suffix,
            'name_fa' => 'City '.$suffix,
        ]);
        $city->save();

        $region = new CityRegion([
            'city_id' => $city->getKey(),
            'code' => 'region'.$suffix,
            'name_fa' => 'Region '.$suffix,
        ]);
        $region->save();

        $area = new CityArea([
            'city_region_id' => $region->getKey(),
            'code' => 'area'.$suffix,
            'name_fa' => 'Area '.$suffix,
        ]);
        $area->save();

        $neighborhood = new Neighborhood([
            'city_id' => $city->getKey(),
            'default_city_region_id' => $region->getKey(),
            'default_city_area_id' => $area->getKey(),
            'code' => 'neighborhood'.$suffix,
            'name_fa' => 'Neighborhood '.$suffix,
        ]);
        $neighborhood->save();

        return [
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'area' => $area,
            'neighborhood' => $neighborhood,
        ];
    }

    /**
     * @return array<int, array{0: Province|City|CityRegion|CityArea|Neighborhood, 1: Province|City|CityRegion|CityArea|Neighborhood}>
     */
    private function createReplacementPairs(): array
    {
        $first = $this->createLocationGraph('-original');
        $second = $this->createLocationGraph('-replacement');

        return [
            [$first['province'], $second['province']],
            [$first['city'], $second['city']],
            [$first['region'], $second['region']],
            [$first['area'], $second['area']],
            [$first['neighborhood'], $second['neighborhood']],
        ];
    }
}
