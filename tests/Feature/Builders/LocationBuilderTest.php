<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Builders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\TestCase;

class LocationBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_common_builder_filters_work(): void
    {
        $active = Province::create([
            'code' => 'province-active',
            'name_fa' => 'Alpha',
            'source' => 'package',
        ]);

        $inactive = Province::create([
            'code' => 'province-inactive',
            'name_fa' => 'Beta',
            'is_active' => false,
            'source' => 'custom',
        ]);

        $deprecated = Province::create([
            'code' => 'province-deprecated',
            'name_fa' => 'Gamma',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-03-01'),
            'source' => 'package',
        ]);

        DB::table($active->getTable())->where('id', $active->getKey())->update(['updated_at' => '2026-01-01 00:00:00']);
        DB::table($inactive->getTable())->where('id', $inactive->getKey())->update(['updated_at' => '2026-02-01 00:00:00']);
        DB::table($deprecated->getTable())->where('id', $deprecated->getKey())->update(['updated_at' => '2026-01-15 00:00:00']);

        self::assertTrue(Province::query()->active()->first()->is($active));
        self::assertTrue(Province::query()->inactive()->whereKey($inactive->getKey())->exists());
        self::assertTrue(Province::query()->deprecated()->first()->is($deprecated));
        self::assertSame(2, Province::query()->notDeprecated()->count());
        self::assertSame(2, Province::query()->package()->count());
        self::assertSame(1, Province::query()->custom()->count());
        self::assertSame(3, Province::query()->source('all')->count());
        self::assertTrue(Province::query()->byCode('province-active')->first()->is($active));
        self::assertTrue(Province::query()->bySlug($active->getAttribute('slug'))->first()->is($active));
        self::assertSame(['province-active', 'province-inactive', 'province-deprecated'], Province::query()->ordered()->pluck('code')->all());
        self::assertSame('province-inactive', Province::query()->latestUpdated()->first()->getAttribute('code'));
        self::assertSame(3, Province::query()->filter(['unknown' => 'ignored'])->count());
        self::assertSame(3, Province::query()->applySort('name;drop table users')->count());
    }

    public function test_search_uses_normalizer_alias_config_and_safe_grouping(): void
    {
        $province = new Province([
            'code' => 'province-search',
            'name_fa' => 'Needle Province',
        ]);
        $province->save();
        $province->aliases()->create([
            'alias' => 'Alias Province',
        ]);

        Province::create([
            'code' => 'province-inactive-search',
            'name_fa' => 'Needle Province',
            'is_active' => false,
        ]);

        self::assertTrue(Province::query()->search('Needle Province')->first()->is($province));
        self::assertTrue(Province::query()->search('Alias Province')->first()->is($province));

        config()->set('iran-locations.search.include_aliases', false);

        self::assertNull(Province::query()->search('Alias Province')->first());
        self::assertSame(2, Province::query()->search(null)->count());
        self::assertSame(2, Province::query()->search('')->count());
        self::assertSame(1, Province::query()->active()->search('Needle Province')->count());
    }

    public function test_province_filter_supports_known_filters(): void
    {
        $records = $this->createGraph('a');
        Province::create([
            'code' => 'province-empty',
            'name_fa' => 'Empty',
            'source' => 'custom',
        ]);

        $result = Province::query()
            ->filter([
                'q' => 'Province a',
                'status' => 'active',
                'source' => 'package',
                'has_cities' => 'yes',
                'sort' => '-code',
            ])
            ->get();

        self::assertCount(1, $result);
        self::assertTrue($result->first()->is($records['province']));
    }

    public function test_city_builder_specific_filters_work(): void
    {
        $records = $this->createGraph('city');
        $other = $this->createGraph('other');

        self::assertTrue(City::query()->forProvince($records['province'])->first()->is($records['city']));
        self::assertTrue(City::query()->forProvince($records['province']->getKey())->first()->is($records['city']));
        self::assertTrue(City::query()->forProvince($records['province']->getAttribute('code'))->first()->is($records['city']));
        self::assertTrue(City::query()->forProvinceCode($records['province']->getAttribute('code'))->first()->is($records['city']));
        self::assertTrue(City::query()->capital()->first()->is($records['city']));
        self::assertTrue(City::query()->notCapital()->first()->is($other['city']));
        self::assertTrue(City::query()->hasRegions()->first()->is($records['city']));
        self::assertTrue(City::query()->hasNeighborhoods()->first()->is($records['city']));

        $filtered = City::query()->filter([
            'province_id' => $records['province']->getKey(),
            'province_code' => $records['province']->getAttribute('code'),
            'is_capital' => '1',
            'has_regions' => 'true',
            'has_neighborhoods' => 'yes',
        ])->first();

        self::assertTrue($filtered->is($records['city']));
    }

    public function test_city_region_builder_specific_filters_work(): void
    {
        $records = $this->createGraph('region');

        self::assertTrue(CityRegion::query()->forCity($records['city'])->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->forCity($records['city']->getKey())->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->forCity($records['city']->getAttribute('code'))->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->forCityCode($records['city']->getAttribute('code'))->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->number('3')->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->municipal()->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->type('municipal_region')->first()->is($records['region']));
        self::assertTrue(CityRegion::query()->orderedByNumber()->first()->is($records['region']));

        $filtered = CityRegion::query()->filter([
            'city_id' => $records['city']->getKey(),
            'city_code' => $records['city']->getAttribute('code'),
            'number' => 3,
            'type' => 'municipal_region',
        ])->first();

        self::assertTrue($filtered->is($records['region']));
    }

    public function test_city_area_builder_specific_filters_work(): void
    {
        $records = $this->createGraph('area');

        self::assertTrue(CityArea::query()->forRegion($records['region'])->first()->is($records['area']));
        self::assertTrue(CityArea::query()->forRegion($records['region']->getKey())->first()->is($records['area']));
        self::assertTrue(CityArea::query()->forRegion($records['region']->getAttribute('code'))->first()->is($records['area']));
        self::assertTrue(CityArea::query()->forCity($records['city'])->first()->is($records['area']));
        self::assertTrue(CityArea::query()->forCity($records['city']->getKey())->first()->is($records['area']));
        self::assertTrue(CityArea::query()->forCityCode($records['city']->getAttribute('code'))->first()->is($records['area']));
        self::assertTrue(CityArea::query()->number('7')->first()->is($records['area']));
        self::assertTrue(CityArea::query()->orderedByNumber()->first()->is($records['area']));

        $filtered = CityArea::query()->filter([
            'city_id' => $records['city']->getKey(),
            'city_code' => $records['city']->getAttribute('code'),
            'region_id' => $records['region']->getKey(),
            'region_code' => $records['region']->getAttribute('code'),
            'number' => 7,
        ])->first();

        self::assertTrue($filtered->is($records['area']));
    }

    public function test_neighborhood_builder_specific_filters_work(): void
    {
        $records = $this->createGraph('neighborhood');
        $missingRegion = Neighborhood::create([
            'city_id' => $records['city']->getKey(),
            'code' => 'neighborhood-missing-region',
            'name_fa' => 'Missing Region',
            'type' => 'street',
        ]);

        self::assertTrue(Neighborhood::query()->forCity($records['city'])->whereKey($records['neighborhood']->getKey())->exists());
        self::assertTrue(Neighborhood::query()->forCity($records['city']->getKey())->whereKey($records['neighborhood']->getKey())->exists());
        self::assertTrue(Neighborhood::query()->forCityCode($records['city']->getAttribute('code'))->whereKey($records['neighborhood']->getKey())->exists());
        self::assertTrue(Neighborhood::query()->forRegion($records['region'])->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->forRegion($records['region']->getKey())->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->forRegionCode($records['region']->getAttribute('code'))->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->forArea($records['area'])->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->forArea($records['area']->getKey())->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->type('neighborhood')->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->neighborhoods()->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->streets()->first()->is($missingRegion));
        self::assertTrue(Neighborhood::query()->hasRegion()->first()->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->missingRegion()->first()->is($missingRegion));

        self::assertSame(0, Neighborhood::query()->boulevards()->count());
        self::assertSame(0, Neighborhood::query()->squares()->count());
        self::assertSame(0, Neighborhood::query()->highways()->count());
        self::assertSame(0, Neighborhood::query()->parks()->count());
        self::assertSame(0, Neighborhood::query()->areas()->count());

        $filtered = Neighborhood::query()->filter([
            'province_id' => $records['province']->getKey(),
            'province_code' => $records['province']->getAttribute('code'),
            'city_id' => $records['city']->getKey(),
            'city_code' => $records['city']->getAttribute('code'),
            'region_id' => $records['region']->getKey(),
            'region_code' => $records['region']->getAttribute('code'),
            'area_id' => $records['area']->getKey(),
            'area_code' => $records['area']->getAttribute('code'),
            'type' => 'neighborhood',
            'has_region' => 'yes',
        ])->first();

        self::assertTrue($filtered->is($records['neighborhood']));
        self::assertTrue(Neighborhood::query()->filter(['missing_region' => true])->first()->is($missingRegion));
        self::assertTrue(Neighborhood::query()->filter(['has_region' => '0'])->first()->is($missingRegion));
    }

    /**
     * @return array<string, Province|City|CityRegion|CityArea|Neighborhood>
     */
    private function createGraph(string $suffix): array
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
            'is_province_capital' => $suffix !== 'other',
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

    private function fakeNormalizer(): LocationNormalizer
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
