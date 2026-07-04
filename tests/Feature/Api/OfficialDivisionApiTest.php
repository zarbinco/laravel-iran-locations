<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

use Zarbin\IranLocations\Models\RuralDistrict;

class OfficialDivisionApiTest extends ApiTestCase
{
    public function test_counties_endpoint_and_nested_province_endpoint_filter_correctly(): void
    {
        $records = $this->createLocationGraph('official-api');
        $other = $this->createLocationGraph('official-other-api');

        $this->getJson('/iran-locations/api/counties?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'county-official-api')
            ->assertJsonPath('data.0.province.code', 'province-official-api')
            ->assertJsonMissing(['code' => 'county-official-other-api']);

        $this->getJson('/iran-locations/api/provinces/'.$other['province']->getAttribute('code').'/counties')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'county-official-other-api')
            ->assertJsonMissing(['code' => 'county-official-api']);
    }

    public function test_county_and_official_district_nested_endpoints_filter_correctly(): void
    {
        $records = $this->createLocationGraph('nested-official-api');
        $this->createLocationGraph('nested-official-other-api');

        $this->getJson('/iran-locations/api/counties/'.$records['county']->getAttribute('code').'/official-districts')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'official-district-nested-official-api')
            ->assertJsonMissing(['code' => 'official-district-nested-official-other-api']);

        $this->getJson('/iran-locations/api/counties/'.$records['county']->getKey().'/cities')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-nested-official-api')
            ->assertJsonPath('data.0.county.code', 'county-nested-official-api');

        $this->getJson('/iran-locations/api/official-districts/'.$records['officialDistrict']->getAttribute('code').'/cities')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-nested-official-api')
            ->assertJsonPath('data.0.official_district.code', 'official-district-nested-official-api');
    }

    public function test_rural_district_endpoints_filter_and_return_resources(): void
    {
        $records = $this->createLocationGraph('rural-api');
        $this->createLocationGraph('rural-other-api');

        $this->getJson('/iran-locations/api/rural-districts?official_district_id='.$records['officialDistrict']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'rural-district-rural-api')
            ->assertJsonPath('data.0.official_district.code', 'official-district-rural-api')
            ->assertJsonMissing(['code' => 'rural-district-rural-other-api']);

        $this->getJson('/iran-locations/api/counties/'.$records['county']->getKey().'/rural-districts')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'rural-district-rural-api');

        $this->getJson('/iran-locations/api/official-districts/'.$records['officialDistrict']->getKey().'/rural-districts')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'rural-district-rural-api');
    }

    public function test_missing_parent_returns_json_404(): void
    {
        $this->getJson('/iran-locations/api/counties/missing-county/cities')
            ->assertNotFound()
            ->assertJsonPath('message', 'County not found.');

        $this->getJson('/iran-locations/api/official-districts/missing-official/rural-districts')
            ->assertNotFound()
            ->assertJsonPath('message', 'Official district not found.');
    }

    public function test_inactive_and_deprecated_official_records_are_excluded_by_default(): void
    {
        $records = $this->createLocationGraph('active-official-api');

        RuralDistrict::query()->create([
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $records['officialDistrict']->getKey(),
            'code' => 'rural-inactive-api',
            'name_fa' => 'Inactive Rural',
            'normalized_name' => 'inactive rural',
            'is_active' => false,
        ]);

        $this->getJson('/iran-locations/api/rural-districts?official_district_id='.$records['officialDistrict']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'rural-district-active-official-api')
            ->assertJsonMissing(['code' => 'rural-inactive-api']);
    }
}
