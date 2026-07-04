<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
use Zarbin\IranLocations\Tests\TestCase;

class ModelConfigurationTest extends TestCase
{
    public function test_default_table_names_work(): void
    {
        self::assertSame('iran_provinces', (new Province)->getTable());
        self::assertSame('iran_counties', (new County)->getTable());
        self::assertSame('iran_official_districts', (new OfficialDistrict)->getTable());
        self::assertSame('iran_rural_districts', (new RuralDistrict)->getTable());
        self::assertSame('iran_cities', (new City)->getTable());
        self::assertSame('iran_city_regions', (new CityRegion)->getTable());
        self::assertSame('iran_city_areas', (new CityArea)->getTable());
        self::assertSame('iran_neighborhoods', (new Neighborhood)->getTable());
        self::assertSame('iran_location_aliases', (new LocationAlias)->getTable());
        self::assertSame('iran_location_data_versions', (new LocationDataVersion)->getTable());
    }

    public function test_model_table_name_comes_from_singular_config(): void
    {
        config()->set('iran-locations.tables.province', 'custom_provinces');

        self::assertSame('custom_provinces', (new Province)->getTable());
    }

    public function test_legacy_plural_table_config_keys_still_work(): void
    {
        config()->set('iran-locations.tables.city', 'iran_cities');
        config()->set('iran-locations.tables.cities', 'custom_cities');

        self::assertSame('custom_cities', (new City)->getTable());
    }

    public function test_pivot_table_name_is_config_driven(): void
    {
        config()->set('iran-locations.tables.neighborhood_region', 'custom_neighborhood_region');

        $relation = (new Neighborhood)->regions();

        self::assertInstanceOf(BelongsToMany::class, $relation);
        self::assertSame('custom_neighborhood_region', $relation->getTable());
    }

    public function test_configured_model_classes_are_used_in_relationships(): void
    {
        config()->set('iran-locations.models.province', ConfiguredProvince::class);
        config()->set('iran-locations.models.county', ConfiguredCounty::class);
        config()->set('iran-locations.models.official_district', ConfiguredOfficialDistrict::class);
        config()->set('iran-locations.models.rural_district', ConfiguredRuralDistrict::class);
        config()->set('iran-locations.models.city', ConfiguredCity::class);
        config()->set('iran-locations.models.city_region', ConfiguredCityRegion::class);
        config()->set('iran-locations.models.city_area', ConfiguredCityArea::class);
        config()->set('iran-locations.models.neighborhood', ConfiguredNeighborhood::class);
        config()->set('iran-locations.models.location_alias', ConfiguredLocationAlias::class);

        self::assertInstanceOf(ConfiguredCounty::class, (new Province)->counties()->getRelated());
        self::assertInstanceOf(ConfiguredOfficialDistrict::class, (new Province)->officialDistricts()->getRelated());
        self::assertInstanceOf(ConfiguredRuralDistrict::class, (new Province)->ruralDistricts()->getRelated());
        self::assertInstanceOf(ConfiguredCity::class, (new Province)->cities()->getRelated());
        self::assertInstanceOf(ConfiguredProvince::class, (new County)->province()->getRelated());
        self::assertInstanceOf(ConfiguredOfficialDistrict::class, (new County)->officialDistricts()->getRelated());
        self::assertInstanceOf(ConfiguredCity::class, (new County)->cities()->getRelated());
        self::assertInstanceOf(ConfiguredRuralDistrict::class, (new County)->ruralDistricts()->getRelated());
        self::assertInstanceOf(ConfiguredProvince::class, (new OfficialDistrict)->province()->getRelated());
        self::assertInstanceOf(ConfiguredCounty::class, (new OfficialDistrict)->county()->getRelated());
        self::assertInstanceOf(ConfiguredCity::class, (new OfficialDistrict)->cities()->getRelated());
        self::assertInstanceOf(ConfiguredRuralDistrict::class, (new OfficialDistrict)->ruralDistricts()->getRelated());
        self::assertInstanceOf(ConfiguredProvince::class, (new RuralDistrict)->province()->getRelated());
        self::assertInstanceOf(ConfiguredCounty::class, (new RuralDistrict)->county()->getRelated());
        self::assertInstanceOf(ConfiguredOfficialDistrict::class, (new RuralDistrict)->officialDistrict()->getRelated());
        self::assertInstanceOf(ConfiguredProvince::class, (new City)->province()->getRelated());
        self::assertInstanceOf(ConfiguredCounty::class, (new City)->county()->getRelated());
        self::assertInstanceOf(ConfiguredOfficialDistrict::class, (new City)->officialDistrict()->getRelated());
        self::assertInstanceOf(ConfiguredCityRegion::class, (new City)->regions()->getRelated());
        self::assertInstanceOf(ConfiguredNeighborhood::class, (new City)->neighborhoods()->getRelated());
        self::assertInstanceOf(ConfiguredCity::class, (new CityRegion)->city()->getRelated());
        self::assertInstanceOf(ConfiguredCityArea::class, (new CityRegion)->areas()->getRelated());
        self::assertInstanceOf(ConfiguredNeighborhood::class, (new CityRegion)->neighborhoods()->getRelated());
        self::assertInstanceOf(ConfiguredCityRegion::class, (new CityArea)->region()->getRelated());
        self::assertInstanceOf(ConfiguredNeighborhood::class, (new CityArea)->neighborhoods()->getRelated());
        self::assertInstanceOf(ConfiguredCity::class, (new Neighborhood)->city()->getRelated());
        self::assertInstanceOf(ConfiguredCityRegion::class, (new Neighborhood)->defaultRegion()->getRelated());
        self::assertInstanceOf(ConfiguredCityArea::class, (new Neighborhood)->defaultArea()->getRelated());
        self::assertInstanceOf(ConfiguredCityRegion::class, (new Neighborhood)->regions()->getRelated());
        self::assertInstanceOf(ConfiguredLocationAlias::class, (new Province)->aliases()->getRelated());
        self::assertInstanceOf(ConfiguredProvince::class, (new Province)->replacedBy()->getRelated());
    }

    public function test_route_key_config_defaults_and_overrides_work(): void
    {
        self::assertSame('id', (new Province)->getRouteKeyName());

        config()->set('iran-locations.route_key', 'code');
        self::assertSame('code', (new City)->getRouteKeyName());

        config()->set('iran-locations.route_key', 'slug');
        self::assertSame('slug', (new Neighborhood)->getRouteKeyName());

        config()->set('iran-locations.route_key', 'missing_column');
        self::assertSame('id', (new CityRegion)->getRouteKeyName());
    }

    public function test_display_name_fallback_is_consistent(): void
    {
        $models = [
            new Province(['name_fa' => 'Tehran']),
            new County(['name_fa' => 'Tehran']),
            new OfficialDistrict(['name_fa' => 'Central']),
            new RuralDistrict(['name_fa' => 'Siahroud']),
            new City(['name_fa' => 'Tehran']),
            new CityRegion(['name_fa' => 'Region 1']),
            new CityArea(['name_fa' => 'Area 1']),
            new Neighborhood(['name_fa' => 'Neighborhood']),
        ];

        foreach ($models as $model) {
            self::assertSame($model->getAttribute('name_fa'), $model->displayName());

            $model->setAttribute('display_name_fa', 'Display');

            self::assertSame('Display', $model->displayName());
        }
    }

    public function test_status_and_source_helpers_work(): void
    {
        $model = new Province([
            'is_active' => true,
            'source' => 'package',
        ]);

        self::assertTrue($model->isActive());
        self::assertFalse($model->isInactive());
        self::assertTrue($model->isPackageRecord());
        self::assertFalse($model->isCustomRecord());

        $model->markInactive();

        self::assertFalse($model->isActive());
        self::assertTrue($model->isInactive());

        $replacement = new Province;
        $replacement->setAttribute('id', 99);

        $model->markDeprecated($replacement);

        self::assertTrue($model->isDeprecated());
        self::assertSame(99, $model->getAttribute('replaced_by_id'));

        $model->restoreFromDeprecation();

        self::assertTrue($model->isActive());
        self::assertFalse($model->isDeprecated());
        self::assertNull($model->getAttribute('replaced_by_id'));

        $model->setAttribute('source', 'custom');

        self::assertFalse($model->isPackageRecord());
        self::assertTrue($model->isCustomRecord());
    }
}

class ConfiguredProvince extends Province {}

class ConfiguredCounty extends County {}

class ConfiguredOfficialDistrict extends OfficialDistrict {}

class ConfiguredRuralDistrict extends RuralDistrict {}

class ConfiguredCity extends City {}

class ConfiguredCityRegion extends CityRegion {}

class ConfiguredCityArea extends CityArea {}

class ConfiguredNeighborhood extends Neighborhood {}

class ConfiguredLocationAlias extends LocationAlias {}
