<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;
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
        /** @var County $county */
        $county = $records['county'];
        /** @var OfficialDistrict $officialDistrict */
        $officialDistrict = $records['officialDistrict'];
        /** @var RuralDistrict $ruralDistrict */
        $ruralDistrict = $records['ruralDistrict'];
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

        self::assertTrue($province->counties()->first()->is($county));
        self::assertTrue($province->officialDistricts()->first()->is($officialDistrict));
        self::assertTrue($province->ruralDistricts()->first()->is($ruralDistrict));
        self::assertTrue($province->cities()->first()->is($city));
        self::assertTrue($county->province->is($province));
        self::assertTrue($county->officialDistricts()->first()->is($officialDistrict));
        self::assertTrue($county->cities()->first()->is($city));
        self::assertTrue($county->ruralDistricts()->first()->is($ruralDistrict));
        self::assertTrue($officialDistrict->province->is($province));
        self::assertTrue($officialDistrict->county->is($county));
        self::assertTrue($officialDistrict->cities()->first()->is($city));
        self::assertTrue($officialDistrict->ruralDistricts()->first()->is($ruralDistrict));
        self::assertTrue($ruralDistrict->province->is($province));
        self::assertTrue($ruralDistrict->county->is($county));
        self::assertTrue($ruralDistrict->officialDistrict->is($officialDistrict));
        self::assertTrue($city->province->is($province));
        self::assertTrue($city->county->is($county));
        self::assertTrue($city->officialDistrict->is($officialDistrict));
        self::assertTrue($city->regions()->first()->is($region));
        self::assertTrue($city->areas()->first()->is($area));
        self::assertTrue($city->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($region->city->is($city));
        self::assertTrue($region->areas()->first()->is($area));
        self::assertTrue($region->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($region->defaultNeighborhoods()->first()->is($neighborhood));
        self::assertTrue($area->region->is($region));
        self::assertTrue($area->neighborhoods()->first()->is($neighborhood));
        self::assertTrue($neighborhood->city->is($city));
        self::assertTrue($neighborhood->defaultRegion->is($region));
        self::assertTrue($neighborhood->defaultArea->is($area));
        self::assertTrue($neighborhood->regions()->first()->is($region));
    }

    public function test_city_region_all_neighborhoods_query_includes_pivot_and_default_region_records(): void
    {
        $records = $this->createLocationGraph('-all-neighborhoods');

        /** @var City $city */
        $city = $records['city'];
        /** @var CityRegion $region */
        $region = $records['region'];

        $pivotOnly = new Neighborhood([
            'city_id' => $city->getKey(),
            'code' => 'neighborhood-pivot-only',
            'name_fa' => 'Pivot Only',
        ]);
        $pivotOnly->save();
        $pivotOnly->regions()->attach($region->getKey(), [
            'is_primary' => false,
            'source' => 'custom',
        ]);

        $defaultOnly = new Neighborhood([
            'city_id' => $city->getKey(),
            'default_city_region_id' => $region->getKey(),
            'code' => 'neighborhood-default-only',
            'name_fa' => 'Default Only',
        ]);
        $defaultOnly->save();

        self::assertTrue($region->neighborhoods()->whereKey($pivotOnly->getKey())->exists());
        self::assertTrue($region->defaultNeighborhoods()->whereKey($defaultOnly->getKey())->exists());

        $ids = $region->allNeighborhoodsQuery()->pluck('id')->all();

        self::assertContains($records['neighborhood']->getKey(), $ids);
        self::assertContains($pivotOnly->getKey(), $ids);
        self::assertContains($defaultOnly->getKey(), $ids);
    }

    public function test_neighborhood_region_relationships_use_active_mappings_by_default(): void
    {
        $records = $this->createLocationGraph('-active-mapping');
        /** @var City $city */
        $city = $records['city'];
        /** @var CityRegion $region */
        $region = $records['region'];

        $active = new Neighborhood([
            'city_id' => $city->getKey(),
            'code' => 'neighborhood-active-mapping-only',
            'name_fa' => 'Active Mapping',
        ]);
        $active->save();
        $active->allRegions()->attach($region->getKey(), [
            'is_primary' => true,
            'source' => 'custom',
            'is_active' => true,
        ]);

        $deprecated = new Neighborhood([
            'city_id' => $city->getKey(),
            'code' => 'neighborhood-deprecated-mapping',
            'name_fa' => 'Deprecated Mapping',
        ]);
        $deprecated->save();
        $deprecated->allRegions()->attach($region->getKey(), [
            'is_primary' => false,
            'source' => 'package',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-03-01'),
        ]);

        $inactive = new Neighborhood([
            'city_id' => $city->getKey(),
            'code' => 'neighborhood-inactive-mapping',
            'name_fa' => 'Inactive Mapping',
        ]);
        $inactive->save();
        $inactive->allRegions()->attach($region->getKey(), [
            'is_primary' => false,
            'source' => 'package',
            'is_active' => false,
        ]);

        self::assertTrue($active->regions()->whereKey($region->getKey())->exists());
        self::assertFalse($deprecated->regions()->whereKey($region->getKey())->exists());
        self::assertFalse($inactive->regions()->whereKey($region->getKey())->exists());
        self::assertTrue($deprecated->allRegions()->whereKey($region->getKey())->exists());
        self::assertTrue($inactive->allRegions()->whereKey($region->getKey())->exists());

        self::assertTrue($region->neighborhoods()->whereKey($active->getKey())->exists());
        self::assertFalse($region->neighborhoods()->whereKey($deprecated->getKey())->exists());
        self::assertFalse($region->neighborhoods()->whereKey($inactive->getKey())->exists());
        self::assertTrue($region->allNeighborhoods()->whereKey($deprecated->getKey())->exists());
        self::assertTrue($region->allNeighborhoods()->whereKey($inactive->getKey())->exists());
    }

    public function test_alias_relationships_work_for_location_models(): void
    {
        $records = $this->createLocationGraph();
        $expectedTypes = [
            'province' => 'province',
            'county' => 'county',
            'officialDistrict' => 'official_district',
            'ruralDistrict' => 'rural_district',
            'city' => 'city',
            'region' => 'city_region',
            'area' => 'city_area',
            'neighborhood' => 'neighborhood',
        ];

        foreach (['province', 'county', 'officialDistrict', 'ruralDistrict', 'city', 'region', 'area', 'neighborhood'] as $key) {
            $model = $records[$key];
            $alias = $model->aliases()->create([
                'alias' => $key.' alias',
            ]);
            self::assertInstanceOf(LocationAlias::class, $alias);
            $location = $alias->location()->first();

            self::assertTrue($model->aliases()->whereKey($alias->getKey())->exists());
            self::assertSame($expectedTypes[$key], $alias->getAttribute('location_type'));
            self::assertSame($key.' alias', $alias->getAttribute('alias'));
            self::assertNotSame('', $alias->getAttribute('normalized_alias'));
            self::assertNotNull($location);
            self::assertTrue($location->is($model));
        }
    }

    public function test_active_aliases_relationship_filters_lifecycle_without_breaking_aliases(): void
    {
        $records = $this->createLocationGraph('-active-aliases');
        $city = $records['city'];

        $active = $city->aliases()->create([
            'alias' => 'Active Alias',
        ]);
        $inactive = $city->aliases()->create([
            'alias' => 'Inactive Alias',
            'is_active' => false,
        ]);
        $deprecated = $city->aliases()->create([
            'alias' => 'Deprecated Alias',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-04-01'),
        ]);

        self::assertTrue($city->activeAliases()->whereKey($active->getKey())->exists());
        self::assertFalse($city->activeAliases()->whereKey($inactive->getKey())->exists());
        self::assertFalse($city->activeAliases()->whereKey($deprecated->getKey())->exists());
        self::assertSame(3, $city->aliases()->count());

        $created = $city->aliases()->create([
            'alias' => 'Created Through Full Alias Relation',
        ]);

        self::assertInstanceOf(LocationAlias::class, $created);
        self::assertSame(4, $city->aliases()->count());
    }

    public function test_alias_target_normalized_alias_unique_constraint_is_enforced(): void
    {
        $records = $this->createLocationGraph('-duplicate-alias');
        $city = $records['city'];

        $city->aliases()->create([
            'alias' => 'Duplicate Alias',
        ]);

        $this->expectException(QueryException::class);

        $city->aliases()->create([
            'alias' => 'Duplicate Alias',
        ]);
    }

    public function test_location_alias_deprecation_and_restore_do_not_require_replacement_column(): void
    {
        $records = $this->createLocationGraph('-alias-lifecycle');
        $alias = $records['city']->aliases()->create([
            'alias' => 'Lifecycle Alias',
        ]);
        self::assertInstanceOf(LocationAlias::class, $alias);

        $alias->markDeprecated()->save();
        $alias->refresh();

        self::assertFalse($alias->isActive());
        self::assertTrue($alias->isInactive());
        self::assertTrue($alias->isDeprecated());
        self::assertNotNull($alias->getAttribute('deprecated_at'));
        self::assertArrayNotHasKey('replaced_by_id', $alias->getAttributes());

        $alias->restoreFromDeprecation()->save();
        $alias->refresh();

        self::assertTrue($alias->isActive());
        self::assertFalse($alias->isInactive());
        self::assertFalse($alias->isDeprecated());
        self::assertNull($alias->getAttribute('deprecated_at'));
        self::assertArrayNotHasKey('replaced_by_id', $alias->getAttributes());
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
     * @return array<string, Province|County|OfficialDistrict|RuralDistrict|City|CityRegion|CityArea|Neighborhood>
     */
    private function createLocationGraph(string $suffix = ''): array
    {
        $province = new Province([
            'code' => 'province'.$suffix,
            'name_fa' => 'Province '.$suffix,
        ]);
        $province->save();

        $county = new County([
            'province_id' => $province->getKey(),
            'code' => 'county'.$suffix,
            'name_fa' => 'County '.$suffix,
        ]);
        $county->save();

        $officialDistrict = new OfficialDistrict([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'official-district'.$suffix,
            'name_fa' => 'Official District '.$suffix,
        ]);
        $officialDistrict->save();

        $ruralDistrict = new RuralDistrict([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
            'code' => 'rural-district'.$suffix,
            'name_fa' => 'Rural District '.$suffix,
        ]);
        $ruralDistrict->save();

        $city = new City([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
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
            'county' => $county,
            'officialDistrict' => $officialDistrict,
            'ruralDistrict' => $ruralDistrict,
            'city' => $city,
            'region' => $region,
            'area' => $area,
            'neighborhood' => $neighborhood,
        ];
    }

    /**
     * @return array<int, array{0: Province|County|OfficialDistrict|RuralDistrict|City|CityRegion|CityArea|Neighborhood, 1: Province|County|OfficialDistrict|RuralDistrict|City|CityRegion|CityArea|Neighborhood}>
     */
    private function createReplacementPairs(): array
    {
        $first = $this->createLocationGraph('-original');
        $second = $this->createLocationGraph('-replacement');

        return [
            [$first['province'], $second['province']],
            [$first['county'], $second['county']],
            [$first['officialDistrict'], $second['officialDistrict']],
            [$first['ruralDistrict'], $second['ruralDistrict']],
            [$first['city'], $second['city']],
            [$first['region'], $second['region']],
            [$first['area'], $second['area']],
            [$first['neighborhood'], $second['neighborhood']],
        ];
    }
}
