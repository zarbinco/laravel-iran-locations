<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;

class LocationResourceAdminTest extends AdminTestCase
{
    public function test_city_region_area_neighborhood_and_alias_crud_paths_work(): void
    {
        $province = $this->province();

        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => $province->getKey(),
            'code' => 'admin.city',
            'name_fa' => 'Admin City',
            'is_active' => '1',
        ])->assertRedirect();

        $city = City::query()->where('code', 'admin.city')->firstOrFail();
        self::assertSame('custom', $city->getAttribute('source'));

        $this->get(route('iran-locations.admin.cities.index', ['q' => 'Admin', 'province_id' => $province->getKey()]))
            ->assertOk()
            ->assertSee('Admin City');

        $this->post(route('iran-locations.admin.city-regions.store'), [
            'city_id' => $city->getKey(),
            'code' => 'admin.region',
            'number' => 1,
            'name_fa' => 'Admin Region',
            'type' => 'municipal_region',
            'is_active' => '1',
        ])->assertRedirect();

        $region = CityRegion::query()->where('code', 'admin.region')->firstOrFail();

        $this->post(route('iran-locations.admin.city-areas.store'), [
            'city_region_id' => $region->getKey(),
            'code' => 'admin.area',
            'number' => 2,
            'name_fa' => 'Admin Area',
            'is_active' => '1',
        ])->assertRedirect();

        $area = CityArea::query()->where('code', 'admin.area')->firstOrFail();

        $this->post(route('iran-locations.admin.neighborhoods.store'), [
            'city_id' => $city->getKey(),
            'default_city_region_id' => $region->getKey(),
            'default_city_area_id' => $area->getKey(),
            'code' => 'admin.neighborhood',
            'name_fa' => 'Admin Neighborhood',
            'type' => 'neighborhood',
            'is_active' => '1',
        ])->assertRedirect();

        $neighborhood = Neighborhood::query()->where('code', 'admin.neighborhood')->firstOrFail();

        $this->post(route('iran-locations.admin.aliases.store'), [
            'location_type' => 'neighborhood',
            'location_id' => $neighborhood->getKey(),
            'alias' => 'Admin Alias',
        ])->assertRedirect();

        $alias = LocationAlias::query()->where('alias', 'Admin Alias')->firstOrFail();

        self::assertSame(Neighborhood::class, $alias->getAttribute('location_type'));
        self::assertSame('custom', $alias->getAttribute('source'));

        $this->get(route('iran-locations.admin.city-regions.index', ['q' => 'Region']))
            ->assertOk()
            ->assertSee('Admin Region');
        $this->get(route('iran-locations.admin.city-areas.index', ['q' => 'Area']))
            ->assertOk()
            ->assertSee('Admin Area');
        $this->get(route('iran-locations.admin.neighborhoods.index', ['q' => 'Neighborhood', 'type' => 'neighborhood']))
            ->assertOk()
            ->assertSee('Admin Neighborhood');
        $this->get(route('iran-locations.admin.aliases.index', ['q' => 'Alias', 'location_type' => 'neighborhood']))
            ->assertOk()
            ->assertSee('Admin Alias');
    }

    public function test_relationship_validation_is_enforced(): void
    {
        $this->post(route('iran-locations.admin.cities.store'), [
            'province_id' => 999,
            'code' => 'missing.province.city',
            'name_fa' => 'Missing Province',
        ])->assertSessionHasErrors(['province_id']);

        $this->post(route('iran-locations.admin.aliases.store'), [
            'location_type' => 'province',
            'location_id' => 999,
            'alias' => 'Missing Alias',
        ])->assertSessionHasErrors(['location_id']);
    }

    private function province(): Province
    {
        $province = new Province([
            'code' => 'admin.parent-province',
            'name_fa' => 'Parent Province',
            'normalized_name' => 'parent province',
            'source' => 'custom',
        ]);
        $province->save();

        return $province;
    }
}
