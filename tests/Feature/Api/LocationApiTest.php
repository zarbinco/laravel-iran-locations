<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\Province;

class LocationApiTest extends ApiTestCase
{
    public function test_provinces_endpoint_returns_paginated_resource_data(): void
    {
        $records = $this->createLocationGraph('province-api');
        Province::create([
            'code' => 'province-inactive-api',
            'name_fa' => 'Inactive Province',
            'is_active' => false,
        ]);

        $this->getJson('/iran-locations/api/provinces')
            ->assertOk()
            ->assertJsonPath('data.0.id', $records['province']->getKey())
            ->assertJsonPath('data.0.code', 'province-province-api')
            ->assertJsonPath('data.0.name_fa', 'Province province-api')
            ->assertJsonPath('data.0.display_name_fa', 'Province province-api')
            ->assertJsonPath('data.0.source', 'package')
            ->assertJsonMissing(['code' => 'province-inactive-api']);
    }

    public function test_cities_endpoint_filters_by_parent_search_and_sort(): void
    {
        $records = $this->createLocationGraph('city-api');
        $other = $this->createLocationGraph('city-other-api');

        $this->getJson('/iran-locations/api/cities?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-city-api')
            ->assertJsonPath('data.0.province.code', 'province-city-api')
            ->assertJsonMissing(['code' => 'city-city-other-api']);

        $this->getJson('/iran-locations/api/cities?province_code='.$other['province']->getAttribute('code'))
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-city-other-api')
            ->assertJsonMissing(['code' => 'city-city-api']);

        $this->getJson('/iran-locations/api/cities?q=City city-api&sort=-code')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-city-api');
    }

    public function test_city_regions_city_areas_and_neighborhood_filters_work(): void
    {
        $records = $this->createLocationGraph('filters-api');
        $this->createLocationGraph('filters-other-api');

        $this->getJson('/iran-locations/api/city-regions?city_id='.$records['city']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'region-filters-api')
            ->assertJsonMissing(['code' => 'region-filters-other-api']);

        $this->getJson('/iran-locations/api/city-regions?city_code='.$records['city']->getAttribute('code'))
            ->assertOk()
            ->assertJsonPath('data.0.code', 'region-filters-api');

        $this->getJson('/iran-locations/api/city-areas?region_id='.$records['region']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'area-filters-api');

        $this->getJson('/iran-locations/api/city-areas?city_id='.$records['city']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'area-filters-api');

        $this->getJson('/iran-locations/api/neighborhoods?city_id='.$records['city']->getKey().'&region_id='.$records['region']->getKey().'&area_id='.$records['area']->getKey().'&type=neighborhood')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'neighborhood-filters-api')
            ->assertJsonPath('data.0.city.code', 'city-filters-api');
    }

    public function test_nested_endpoints_resolve_parents_by_id_code_and_slug(): void
    {
        $records = $this->createLocationGraph('nested-api');
        $records['city']->forceFill(['slug' => 'city-nested-slug'])->save();

        $this->getJson('/iran-locations/api/provinces/'.$records['province']->getAttribute('code').'/cities')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-nested-api');

        $this->getJson('/iran-locations/api/cities/city-nested-slug/regions')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'region-nested-api');

        $this->getJson('/iran-locations/api/city-regions/'.$records['region']->getAttribute('code').'/areas')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'area-nested-api');

        $this->getJson('/iran-locations/api/city-areas/'.$records['area']->getAttribute('code').'/neighborhoods')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'neighborhood-nested-api');
    }

    public function test_missing_parent_returns_json_404(): void
    {
        $this->getJson('/iran-locations/api/cities/missing-city/neighborhoods')
            ->assertNotFound()
            ->assertJsonPath('message', 'City not found.');
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $this->getJson('/iran-locations/api/provinces?per_page=4')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_status_endpoint_returns_manifest_and_database_counts(): void
    {
        $this->createLocationGraph('status-api');

        $this->getJson('/iran-locations/api/status')
            ->assertOk()
            ->assertJsonPath('data_version', '0.1.0-dev')
            ->assertJsonPath('manifest.counts.provinces', 31)
            ->assertJsonPath('manifest.contains.cities', true)
            ->assertJsonPath('database.counts.provinces', 1);
    }

    public function test_source_and_status_filters_are_applied_to_lists(): void
    {
        $records = $this->createLocationGraph('status-filter-api');

        City::create([
            'province_id' => $records['province']->getKey(),
            'code' => 'city-custom-api',
            'name_fa' => 'Custom City',
            'source' => 'custom',
        ]);

        $this->getJson('/iran-locations/api/cities?source=custom')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-custom-api')
            ->assertJsonMissing(['code' => 'city-status-filter-api']);

        $this->getJson('/iran-locations/api/cities?status=all')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
