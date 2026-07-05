<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\Support\CreatesLocationTestData;

class ConfigContractAdminTest extends AdminTestCase
{
    use CreatesLocationTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindFakeLocationNormalizer();
    }

    public function test_admin_index_requests_enforce_dynamic_search_minimum_length(): void
    {
        config()->set('iran-locations.search.min_length', 4);

        $this->get(route('iran-locations.admin.provinces.index', ['q' => 'abc']))
            ->assertRedirect()
            ->assertSessionHasErrors('q');

        $this->get(route('iran-locations.admin.provinces.index', ['q' => 'abcd']))
            ->assertOk();
    }

    public function test_province_create_form_hides_package_source_option_when_direct_edit_is_disabled(): void
    {
        $this->get(route('iran-locations.admin.provinces.create'))
            ->assertOk()
            ->assertDontSee('value="package"', false)
            ->assertSee('name="source" value="custom"', false);
    }

    public function test_alias_create_form_hides_package_source_option_when_direct_edit_is_disabled(): void
    {
        $this->get(route('iran-locations.admin.aliases.create'))
            ->assertOk()
            ->assertDontSee('value="package"', false)
            ->assertSee('name="source" value="custom"', false);
    }

    public function test_province_create_form_shows_package_source_option_when_direct_edit_is_enabled(): void
    {
        config()->set('iran-locations.data.allow_package_record_direct_edit', true);

        $this->get(route('iran-locations.admin.provinces.create'))
            ->assertOk()
            ->assertSee('value="package"', false);
    }

    public function test_alias_create_form_shows_package_source_option_when_direct_edit_is_enabled(): void
    {
        config()->set('iran-locations.data.allow_package_record_direct_edit', true);

        $this->get(route('iran-locations.admin.aliases.create'))
            ->assertOk()
            ->assertSee('value="package"', false);
    }

    public function test_admin_create_rejects_package_source_when_direct_edit_is_disabled(): void
    {
        $this->post(route('iran-locations.admin.provinces.store'), [
            'code' => 'admin.package-source',
            'name_fa' => 'Package Source',
            'source' => 'package',
        ])->assertSessionHasErrors('source');

        self::assertFalse(Province::query()->where('code', 'admin.package-source')->exists());
    }

    public function test_admin_create_accepts_custom_source_when_direct_edit_is_disabled(): void
    {
        $this->post(route('iran-locations.admin.provinces.store'), [
            'code' => 'admin.custom-source',
            'name_fa' => 'Custom Source',
            'source' => 'custom',
        ])->assertRedirect();

        self::assertSame('custom', Province::query()->where('code', 'admin.custom-source')->firstOrFail()->getAttribute('source'));
    }

    public function test_package_owned_location_updates_are_blocked_when_direct_edit_is_disabled(): void
    {
        $records = $this->createLocationGraph('blocked-admin');
        $alias = $this->packageAlias($records['city']);

        foreach ($this->updateCases($records, $alias) as $case) {
            $this->put(route($case['route'], $case['id']), $case['payload'])
                ->assertForbidden();

            self::assertSame($case['original'], $case['model']->refresh()->getAttribute($case['attribute']));
        }
    }

    public function test_package_owned_update_is_allowed_when_direct_edit_is_enabled(): void
    {
        config()->set('iran-locations.data.allow_package_record_direct_edit', true);

        $province = new Province([
            'code' => 'package-edit-enabled',
            'name_fa' => 'Before Package Edit',
            'source' => 'package',
        ]);
        $province->save();

        $this->put(route('iran-locations.admin.provinces.update', $province->getKey()), [
            'code' => 'package-edit-enabled',
            'name_fa' => 'After Package Edit',
            'source' => 'package',
        ])->assertRedirect();

        self::assertSame('After Package Edit', $province->refresh()->getAttribute('name_fa'));
        self::assertSame('package', $province->getAttribute('source'));
    }

    public function test_package_owned_destroy_is_blocked_when_direct_edit_is_disabled(): void
    {
        $records = $this->createLocationGraph('destroy-blocked-admin');
        $alias = $this->packageAlias($records['city']);

        $this->delete(route('iran-locations.admin.provinces.destroy', $records['province']->getKey()))
            ->assertForbidden();

        self::assertTrue((bool) $records['province']->refresh()->getAttribute('is_active'));
        self::assertNull($records['province']->getAttribute('deprecated_at'));

        $this->delete(route('iran-locations.admin.aliases.destroy', $alias->getKey()))
            ->assertForbidden();

        self::assertTrue(LocationAlias::query()->whereKey($alias->getKey())->exists());
    }

    public function test_alias_package_record_update_is_allowed_when_direct_edit_is_enabled(): void
    {
        config()->set('iran-locations.data.allow_package_record_direct_edit', true);

        $records = $this->createLocationGraph('alias-edit-enabled');
        $alias = $this->packageAlias($records['city']);

        $this->put(route('iran-locations.admin.aliases.update', $alias->getKey()), [
            'location_type' => 'city',
            'location_id' => $records['city']->getKey(),
            'alias' => 'Updated Package Alias',
            'source' => 'package',
        ])->assertRedirect();

        self::assertSame('Updated Package Alias', $alias->refresh()->getAttribute('alias'));
        self::assertSame('package', $alias->getAttribute('source'));
    }

    private function packageAlias(City $city): LocationAlias
    {
        $alias = new LocationAlias([
            'location_type' => City::class,
            'location_id' => $city->getKey(),
            'alias' => 'Package Alias',
            'normalized_alias' => 'package alias',
            'source' => 'package',
        ]);
        $alias->save();

        return $alias;
    }

    /**
     * @param  array<string, Model>  $records
     * @return array<int, array{
     *     route: string,
     *     id: int|string|null,
     *     payload: array<string, mixed>,
     *     model: Model,
     *     attribute: string,
     *     original: mixed
     * }>
     */
    private function updateCases(array $records, LocationAlias $alias): array
    {
        return [
            [
                'route' => 'iran-locations.admin.provinces.update',
                'id' => $records['province']->getKey(),
                'payload' => [
                    'code' => $records['province']->getAttribute('code'),
                    'name_fa' => 'Blocked Province',
                ],
                'model' => $records['province'],
                'attribute' => 'name_fa',
                'original' => $records['province']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.counties.update',
                'id' => $records['county']->getKey(),
                'payload' => [
                    'province_id' => $records['province']->getKey(),
                    'code' => $records['county']->getAttribute('code'),
                    'name_fa' => 'Blocked County',
                ],
                'model' => $records['county'],
                'attribute' => 'name_fa',
                'original' => $records['county']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.official-districts.update',
                'id' => $records['officialDistrict']->getKey(),
                'payload' => [
                    'province_id' => $records['province']->getKey(),
                    'county_id' => $records['county']->getKey(),
                    'code' => $records['officialDistrict']->getAttribute('code'),
                    'name_fa' => 'Blocked Official District',
                ],
                'model' => $records['officialDistrict'],
                'attribute' => 'name_fa',
                'original' => $records['officialDistrict']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.rural-districts.update',
                'id' => $records['ruralDistrict']->getKey(),
                'payload' => [
                    'province_id' => $records['province']->getKey(),
                    'county_id' => $records['county']->getKey(),
                    'official_district_id' => $records['officialDistrict']->getKey(),
                    'code' => $records['ruralDistrict']->getAttribute('code'),
                    'name_fa' => 'Blocked Rural District',
                ],
                'model' => $records['ruralDistrict'],
                'attribute' => 'name_fa',
                'original' => $records['ruralDistrict']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.cities.update',
                'id' => $records['city']->getKey(),
                'payload' => [
                    'province_id' => $records['province']->getKey(),
                    'county_id' => $records['county']->getKey(),
                    'official_district_id' => $records['officialDistrict']->getKey(),
                    'code' => $records['city']->getAttribute('code'),
                    'name_fa' => 'Blocked City',
                ],
                'model' => $records['city'],
                'attribute' => 'name_fa',
                'original' => $records['city']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.city-regions.update',
                'id' => $records['region']->getKey(),
                'payload' => [
                    'city_id' => $records['city']->getKey(),
                    'code' => $records['region']->getAttribute('code'),
                    'number' => $records['region']->getAttribute('number'),
                    'name_fa' => 'Blocked Region',
                    'type' => $records['region']->getAttribute('type'),
                ],
                'model' => $records['region'],
                'attribute' => 'name_fa',
                'original' => $records['region']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.city-areas.update',
                'id' => $records['area']->getKey(),
                'payload' => [
                    'city_region_id' => $records['region']->getKey(),
                    'code' => $records['area']->getAttribute('code'),
                    'number' => $records['area']->getAttribute('number'),
                    'name_fa' => 'Blocked Area',
                ],
                'model' => $records['area'],
                'attribute' => 'name_fa',
                'original' => $records['area']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.neighborhoods.update',
                'id' => $records['neighborhood']->getKey(),
                'payload' => [
                    'city_id' => $records['city']->getKey(),
                    'default_city_region_id' => $records['region']->getKey(),
                    'default_city_area_id' => $records['area']->getKey(),
                    'code' => $records['neighborhood']->getAttribute('code'),
                    'name_fa' => 'Blocked Neighborhood',
                    'type' => $records['neighborhood']->getAttribute('type'),
                ],
                'model' => $records['neighborhood'],
                'attribute' => 'name_fa',
                'original' => $records['neighborhood']->getAttribute('name_fa'),
            ],
            [
                'route' => 'iran-locations.admin.aliases.update',
                'id' => $alias->getKey(),
                'payload' => [
                    'location_type' => 'city',
                    'location_id' => $records['city']->getKey(),
                    'alias' => 'Blocked Alias',
                ],
                'model' => $alias,
                'attribute' => 'alias',
                'original' => $alias->getAttribute('alias'),
            ],
        ];
    }
}
