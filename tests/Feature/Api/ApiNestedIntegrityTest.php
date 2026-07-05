<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

class ApiNestedIntegrityTest extends ApiTestCase
{
    public function test_nested_route_parent_resolution_requires_active_parent(): void
    {
        $records = $this->createLocationGraph('api-active-parent');

        $this->getJson('/iran-locations/api/cities/'.$records['city']->getKey().'/regions')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'region-api-active-parent');

        $records['city']->forceFill(['is_active' => false])->save();

        $this->getJson('/iran-locations/api/cities/'.$records['city']->getKey().'/regions')
            ->assertNotFound()
            ->assertJsonPath('message', 'City not found.');
    }

    public function test_nested_route_parent_resolution_rejects_deprecated_parent(): void
    {
        $records = $this->createLocationGraph('api-deprecated-parent');
        $records['region']->markDeprecated()->save();

        $this->getJson('/iran-locations/api/city-regions/'.$records['region']->getKey().'/neighborhoods')
            ->assertNotFound()
            ->assertJsonPath('message', 'City region not found.');
    }

    public function test_active_parent_can_still_be_resolved_by_code_and_slug(): void
    {
        $records = $this->createLocationGraph('api-parent-route-key');
        $records['city']->forceFill(['slug' => 'active-parent-slug'])->save();

        $this->getJson('/iran-locations/api/provinces/'.$records['province']->getAttribute('code').'/cities')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-api-parent-route-key');

        $this->getJson('/iran-locations/api/cities/active-parent-slug/regions')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'region-api-parent-route-key');
    }

    public function test_status_all_listing_still_includes_inactive_and_deprecated_records(): void
    {
        $records = $this->createLocationGraph('api-status-all-parent');
        $records['city']->forceFill(['is_active' => false])->save();
        $records['region']->markDeprecated()->save();

        $this->getJson('/iran-locations/api/cities?status=all')
            ->assertOk()
            ->assertJsonFragment(['code' => 'city-api-status-all-parent']);

        $this->getJson('/iran-locations/api/city-regions?status=all')
            ->assertOk()
            ->assertJsonFragment(['code' => 'region-api-status-all-parent']);
    }

    public function test_nested_province_filters_reject_conflicts_and_allow_matches(): void
    {
        $records = $this->createLocationGraph('api-province-conflict');
        $other = $this->createLocationGraph('api-province-conflict-other');

        $this->getJson('/iran-locations/api/provinces/'.$records['province']->getKey().'/cities?province_id='.$other['province']->getKey())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_id']);

        $this->getJson('/iran-locations/api/provinces/'.$records['province']->getKey().'/cities?province_code='.$other['province']->getAttribute('code'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_code']);

        $this->getJson('/iran-locations/api/provinces/'.$records['province']->getKey().'/cities?province_id='.$records['province']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'city-api-province-conflict');
    }

    public function test_nested_city_and_region_filters_reject_conflicts_and_allow_matches(): void
    {
        $records = $this->createLocationGraph('api-nested-conflict');
        $other = $this->createLocationGraph('api-nested-conflict-other');

        $this->getJson('/iran-locations/api/cities/'.$records['city']->getKey().'/regions?city_id='.$other['city']->getKey())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);

        $this->getJson('/iran-locations/api/city-regions/'.$records['region']->getKey().'/neighborhoods?region_id='.$other['region']->getKey())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region_id']);

        $this->getJson('/iran-locations/api/city-regions/'.$records['region']->getKey().'/neighborhoods?region_code='.$other['region']->getAttribute('code'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region_code']);

        $this->getJson('/iran-locations/api/city-regions/'.$records['region']->getKey().'/neighborhoods?region_id='.$records['region']->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.code', 'neighborhood-api-nested-conflict');
    }
}
