<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Tests\Support\CreatesLocationTestData;

class AdminHierarchyValidationTest extends AdminTestCase
{
    use CreatesLocationTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindFakeLocationNormalizer();
    }

    public function test_official_district_rejects_county_from_different_province_on_create_and_update(): void
    {
        $records = $this->createLocationGraph('admin-hierarchy-official');
        $other = $this->createLocationGraph('admin-hierarchy-official-other');

        $payload = [
            'province_id' => $records['province']->getKey(),
            'county_id' => $other['county']->getKey(),
            'code' => 'admin.hierarchy.official.invalid',
            'name_fa' => 'Invalid Official District',
        ];

        $this->post(route('iran-locations.admin.official-districts.store'), $payload)
            ->assertSessionHasErrors(['county_id']);

        $this->put(route('iran-locations.admin.official-districts.update', $records['officialDistrict']->getKey()), $payload)
            ->assertSessionHasErrors(['county_id']);
    }

    public function test_rural_district_rejects_parent_hierarchy_mismatches(): void
    {
        $records = $this->createLocationGraph('admin-hierarchy-rural');
        $other = $this->createLocationGraph('admin-hierarchy-rural-other');
        $sameProvinceOtherCounty = $this->createLocationGraph('admin-hierarchy-rural-same-province');
        $sameProvinceOtherCounty['county']->forceFill(['province_id' => $records['province']->getKey()])->save();
        $sameProvinceOtherCounty['officialDistrict']->forceFill([
            'province_id' => $records['province']->getKey(),
            'county_id' => $sameProvinceOtherCounty['county']->getKey(),
        ])->save();

        $this->post(route('iran-locations.admin.rural-districts.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $other['county']->getKey(),
            'official_district_id' => $other['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.rural.bad-county',
            'name_fa' => 'Bad County Rural District',
        ])->assertSessionHasErrors(['county_id']);

        $this->post(route('iran-locations.admin.rural-districts.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $sameProvinceOtherCounty['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.rural.bad-official-county',
            'name_fa' => 'Bad Official County Rural District',
        ])->assertSessionHasErrors(['official_district_id']);

        $this->post(route('iran-locations.admin.rural-districts.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $other['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.rural.bad-official-province',
            'name_fa' => 'Bad Official Province Rural District',
        ])->assertSessionHasErrors(['official_district_id']);
    }

    public function test_city_rejects_parent_hierarchy_mismatches(): void
    {
        $records = $this->createLocationGraph('admin-hierarchy-city');
        $other = $this->createLocationGraph('admin-hierarchy-city-other');
        $sameProvinceOtherCounty = $this->createLocationGraph('admin-hierarchy-city-same-province');
        $sameProvinceOtherCounty['county']->forceFill(['province_id' => $records['province']->getKey()])->save();
        $sameProvinceOtherCounty['officialDistrict']->forceFill([
            'province_id' => $records['province']->getKey(),
            'county_id' => $sameProvinceOtherCounty['county']->getKey(),
        ])->save();

        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $other['county']->getKey(),
            'code' => 'admin.hierarchy.city.bad-county',
            'name_fa' => 'Bad County City',
        ])->assertSessionHasErrors(['county_id']);

        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => $records['province']->getKey(),
            'official_district_id' => $other['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.city.bad-official-province',
            'name_fa' => 'Bad Official Province City',
        ])->assertSessionHasErrors(['official_district_id']);

        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $sameProvinceOtherCounty['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.city.bad-official-county',
            'name_fa' => 'Bad Official County City',
        ])->assertSessionHasErrors(['official_district_id']);
    }

    public function test_neighborhood_rejects_region_and_area_hierarchy_mismatches(): void
    {
        $records = $this->createLocationGraph('admin-hierarchy-neighborhood');
        $other = $this->createLocationGraph('admin-hierarchy-neighborhood-other');

        $otherRegion = new CityRegion([
            'city_id' => $records['city']->getKey(),
            'code' => 'region-admin-hierarchy-neighborhood-other-same-city',
            'number' => 9,
            'name_fa' => 'Other Same City Region',
            'type' => 'municipal_region',
        ]);
        $otherRegion->save();

        $otherRegionArea = new CityArea([
            'city_region_id' => $otherRegion->getKey(),
            'code' => 'area-admin-hierarchy-neighborhood-other-same-city',
            'number' => 9,
            'name_fa' => 'Other Same City Area',
        ]);
        $otherRegionArea->save();

        $this->post(route('iran-locations.admin.neighborhoods.store'), [
            'city_id' => $records['city']->getKey(),
            'default_city_region_id' => $other['region']->getKey(),
            'code' => 'admin.hierarchy.neighborhood.bad-region',
            'name_fa' => 'Bad Region Neighborhood',
            'type' => 'neighborhood',
        ])->assertSessionHasErrors(['default_city_region_id']);

        $this->post(route('iran-locations.admin.neighborhoods.store'), [
            'city_id' => $records['city']->getKey(),
            'default_city_area_id' => $other['area']->getKey(),
            'code' => 'admin.hierarchy.neighborhood.bad-area-city',
            'name_fa' => 'Bad Area City Neighborhood',
            'type' => 'neighborhood',
        ])->assertSessionHasErrors(['default_city_area_id']);

        $this->post(route('iran-locations.admin.neighborhoods.store'), [
            'city_id' => $records['city']->getKey(),
            'default_city_region_id' => $records['region']->getKey(),
            'default_city_area_id' => $otherRegionArea->getKey(),
            'code' => 'admin.hierarchy.neighborhood.bad-area-region',
            'name_fa' => 'Bad Area Region Neighborhood',
            'type' => 'neighborhood',
        ])->assertSessionHasErrors(['default_city_area_id']);
    }

    public function test_matching_admin_hierarchy_passes(): void
    {
        $records = $this->createLocationGraph('admin-hierarchy-valid');

        $this->post(route('iran-locations.admin.official-districts.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'code' => 'admin.hierarchy.official.valid',
            'name_fa' => 'Valid Official District',
        ])->assertRedirect();

        $this->post(route('iran-locations.admin.rural-districts.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $records['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.rural.valid',
            'name_fa' => 'Valid Rural District',
        ])->assertRedirect();

        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => $records['province']->getKey(),
            'county_id' => $records['county']->getKey(),
            'official_district_id' => $records['officialDistrict']->getKey(),
            'code' => 'admin.hierarchy.city.valid',
            'name_fa' => 'Valid City',
        ])->assertRedirect();

        $this->post(route('iran-locations.admin.neighborhoods.store'), [
            'city_id' => $records['city']->getKey(),
            'default_city_region_id' => $records['region']->getKey(),
            'default_city_area_id' => $records['area']->getKey(),
            'code' => 'admin.hierarchy.neighborhood.valid',
            'name_fa' => 'Valid Neighborhood',
            'type' => 'neighborhood',
        ])->assertRedirect();
    }
}
