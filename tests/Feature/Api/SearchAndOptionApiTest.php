<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Models\City;

class SearchAndOptionApiTest extends ApiTestCase
{
    public function test_search_endpoint_requires_query(): void
    {
        $this->getJson('/iran-locations/api/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_endpoint_uses_builder_normalization_and_returns_grouped_results(): void
    {
        $records = $this->createLocationGraph('search-api');

        $this->getJson('/iran-locations/api/search?q=City search-api&limit=1')
            ->assertOk()
            ->assertJsonPath('query', 'City search-api')
            ->assertJsonPath('results.cities.0.code', 'city-search-api')
            ->assertJsonPath('results.provinces', [])
            ->assertJsonStructure([
                'results' => ['provinces', 'counties', 'official_districts', 'rural_districts', 'cities', 'city_regions', 'city_areas', 'neighborhoods'],
            ]);

        $this->getJson('/iran-locations/api/search?q=Official District search-api&limit=1')
            ->assertOk()
            ->assertJsonPath('results.official_districts.0.code', 'official-district-search-api');

        $records['province']->aliases()->create([
            'alias' => 'Alias Search Province',
        ]);

        $this->getJson('/iran-locations/api/search?q=Alias Search Province&limit=1')
            ->assertOk()
            ->assertJsonPath('results.provinces.0.code', 'province-search-api');

        $records['city']->aliases()->create([
            'alias' => 'Deprecated Search City',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-05-01'),
        ]);

        $this->getJson('/iran-locations/api/search?q=Deprecated Search City&limit=1')
            ->assertOk()
            ->assertJsonPath('results.cities', []);
    }

    public function test_search_endpoint_applies_query_to_fallback_eloquent_models(): void
    {
        config()->set('iran-locations.models.province', FallbackSearchProvince::class);

        FallbackSearchProvince::query()->create([
            'code' => 'fallback-province-needle',
            'name_fa' => 'Needle Province',
            'normalized_name' => 'normalized:Needle Province',
            'slug' => 'needle-province',
            'is_active' => true,
            'source' => 'custom',
        ]);
        FallbackSearchProvince::query()->create([
            'code' => 'fallback-province-other',
            'name_fa' => 'Other Province',
            'normalized_name' => 'normalized:Other Province',
            'slug' => 'other-province',
            'is_active' => true,
            'source' => 'custom',
        ]);

        $this->getJson('/iran-locations/api/search?q=Needle Province&limit=3')
            ->assertOk()
            ->assertJsonCount(1, 'results.provinces')
            ->assertJsonPath('results.provinces.0.code', 'fallback-province-needle')
            ->assertJsonMissing(['code' => 'fallback-province-other']);
    }

    public function test_alias_endpoint_filters_aliases(): void
    {
        $records = $this->createLocationGraph('alias-api');

        $alias = $records['city']->aliases()->create([
            'alias' => 'Alias City API',
            'source' => 'custom',
        ]);

        $this->getJson('/iran-locations/api/aliases?q=Alias City&location_type=city&source=custom')
            ->assertOk()
            ->assertJsonPath('data.0.id', $alias->getKey())
            ->assertJsonPath('data.0.location_type', 'city')
            ->assertJsonPath('data.0.location_id', $records['city']->getKey())
            ->assertJsonPath('data.0.normalized_alias', 'normalized:Alias City API')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.deprecated_at', null);
    }

    public function test_alias_endpoint_defaults_to_active_and_supports_status_filters(): void
    {
        $records = $this->createLocationGraph('alias-status-api');

        $active = $records['city']->aliases()->create([
            'alias' => 'Status Alias Active',
        ]);
        $deprecated = $records['city']->aliases()->create([
            'alias' => 'Status Alias Deprecated',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-06-01'),
        ]);
        $inactive = $records['city']->aliases()->create([
            'alias' => 'Status Alias Inactive',
            'is_active' => false,
        ]);

        $this->getJson('/iran-locations/api/aliases?q=Status Alias&per_page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->getKey())
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.deprecated_at', null);

        $this->getJson('/iran-locations/api/aliases?q=Status Alias&status=all&per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/iran-locations/api/aliases?q=Status Alias Deprecated&status=deprecated')
            ->assertOk()
            ->assertJsonPath('data.0.id', $deprecated->getKey())
            ->assertJsonPath('data.0.is_active', false);

        $this->getJson('/iran-locations/api/aliases?q=Status Alias Inactive&status=inactive')
            ->assertOk()
            ->assertJsonPath('data.0.id', $inactive->getKey())
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.0.deprecated_at', null);
    }

    public function test_alias_endpoint_rejects_unsupported_location_type_filter(): void
    {
        $this->getJson('/iran-locations/api/aliases?location_type='.urlencode(City::class))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('location_type');
    }

    public function test_option_endpoints_return_lightweight_active_records_with_parent_filters(): void
    {
        $records = $this->createLocationGraph('options-api');
        $this->createLocationGraph('options-other-api');

        City::create([
            'province_id' => $records['province']->getKey(),
            'code' => 'city-inactive-options-api',
            'name_fa' => 'Inactive City',
            'is_active' => false,
        ]);

        $this->getJson('/iran-locations/api/options/cities?province_id='.$records['province']->getKey().'&limit=3')
            ->assertOk()
            ->assertJsonPath('0.value', $records['city']->getKey())
            ->assertJsonPath('0.code', 'city-options-api')
            ->assertJsonPath('0.label', 'City options-api')
            ->assertJsonMissing(['code' => 'city-options-other-api'])
            ->assertJsonMissing(['code' => 'city-inactive-options-api']);

        $this->getJson('/iran-locations/api/options/counties?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'county-options-api')
            ->assertJsonMissing(['code' => 'county-options-other-api']);

        $this->getJson('/iran-locations/api/options/official-districts?county_id='.$records['county']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'official-district-options-api')
            ->assertJsonMissing(['code' => 'official-district-options-other-api']);

        $this->getJson('/iran-locations/api/options/rural-districts?official_district_id='.$records['officialDistrict']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'rural-district-options-api')
            ->assertJsonMissing(['code' => 'rural-district-options-other-api']);

        $this->getJson('/iran-locations/api/options/cities?county_id='.$records['county']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'city-options-api')
            ->assertJsonMissing(['code' => 'city-options-other-api']);

        $this->getJson('/iran-locations/api/options/city-regions?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'region-options-api')
            ->assertJsonMissing(['code' => 'region-options-other-api']);

        $this->getJson('/iran-locations/api/options/city-regions?county_id='.$records['county']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'region-options-api')
            ->assertJsonMissing(['code' => 'region-options-other-api']);

        $this->getJson('/iran-locations/api/options/city-regions?official_district_id='.$records['officialDistrict']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'region-options-api')
            ->assertJsonMissing(['code' => 'region-options-other-api']);

        $this->getJson('/iran-locations/api/options/city-areas?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'area-options-api')
            ->assertJsonMissing(['code' => 'area-options-other-api']);

        $this->getJson('/iran-locations/api/options/city-areas?county_id='.$records['county']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'area-options-api')
            ->assertJsonMissing(['code' => 'area-options-other-api']);

        $this->getJson('/iran-locations/api/options/neighborhoods?city_id='.$records['city']->getKey().'&region_id='.$records['region']->getKey().'&type=neighborhood')
            ->assertOk()
            ->assertJsonPath('0.code', 'neighborhood-options-api');

        $this->getJson('/iran-locations/api/options/neighborhoods?county_id='.$records['county']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'neighborhood-options-api')
            ->assertJsonMissing(['code' => 'neighborhood-options-other-api']);

        $this->getJson('/iran-locations/api/options/neighborhoods?official_district_id='.$records['officialDistrict']->getKey())
            ->assertOk()
            ->assertJsonPath('0.code', 'neighborhood-options-api')
            ->assertJsonMissing(['code' => 'neighborhood-options-other-api']);
    }

    public function test_option_endpoints_support_query_and_limit(): void
    {
        $this->createLocationGraph('limit-a-api');
        $this->createLocationGraph('limit-b-api');

        $this->getJson('/iran-locations/api/options/provinces?q=Province limit&limit=1')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name_fa', 'Province limit-a-api');
    }
}

class FallbackSearchProvince extends Model
{
    protected $table = 'iran_provinces';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
