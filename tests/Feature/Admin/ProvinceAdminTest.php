<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\Province;

class ProvinceAdminTest extends AdminTestCase
{
    public function test_province_index_create_store_update_and_destroy_work_safely(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $this->get(route('iran-locations.admin.provinces.index'))
            ->assertOk()
            ->assertSee('Provinces');

        $this->get(route('iran-locations.admin.provinces.create'))
            ->assertOk()
            ->assertSee('Create province');

        $this->post(route('iran-locations.admin.provinces.store'), [
            'code' => 'admin.province',
            'name_fa' => 'Admin Province',
            'is_active' => '1',
        ])->assertRedirect();

        $province = Province::query()->where('code', 'admin.province')->firstOrFail();

        self::assertSame('custom', $province->getAttribute('source'));
        self::assertSame('search:Admin Province', $province->getAttribute('normalized_name'));

        $this->get(route('iran-locations.admin.provinces.index', ['q' => 'Admin', 'sort' => 'name']))
            ->assertOk()
            ->assertSee('Admin Province');

        $this->put(route('iran-locations.admin.provinces.update', $province->getKey()), [
            'code' => 'admin.province',
            'name_fa' => 'Updated Province',
            'display_name_fa' => 'Display Province',
            'is_active' => '1',
            'source' => 'custom',
        ])->assertRedirect();

        $province->refresh();

        self::assertSame('Updated Province', $province->getAttribute('name_fa'));
        self::assertSame('Display Province', $province->getAttribute('display_name_fa'));

        $this->delete(route('iran-locations.admin.provinces.destroy', $province->getKey()))
            ->assertRedirect();

        self::assertFalse(Province::query()->whereKey($province->getKey())->exists());
    }

    public function test_package_owned_province_destroy_deprecates_instead_of_deleting(): void
    {
        $province = $this->province('package.province', 'Package Province', 'package');

        $this->delete(route('iran-locations.admin.provinces.destroy', $province->getKey()))
            ->assertRedirect();

        $province->refresh();

        self::assertFalse((bool) $province->getAttribute('is_active'));
        self::assertNotNull($province->getAttribute('deprecated_at'));
    }

    public function test_validation_errors_are_shown(): void
    {
        $this->post(route('iran-locations.admin.provinces.store'), [])
            ->assertSessionHasErrors(['code', 'name_fa']);
    }

    private function province(string $code, string $name, string $source = 'custom'): Province
    {
        $province = new Province([
            'code' => $code,
            'name_fa' => $name,
            'normalized_name' => $name,
            'source' => $source,
        ]);
        $province->save();

        return $province;
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
                return 'search:'.$value;
            }

            public function slug(string $value): string
            {
                return 'slug:'.$value;
            }
        };
    }
}
