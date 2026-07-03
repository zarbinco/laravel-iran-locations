<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\Province;

class AdminOptionsAliasSearchTest extends AdminTestCase
{
    public function test_relation_options_are_not_limited_to_500_records(): void
    {
        $province = $this->province();

        for ($i = 1; $i <= 501; $i++) {
            $this->city($province, sprintf('admin.city.%03d', $i), "Option City {$i}");
        }

        $this->get(route('iran-locations.admin.city-regions.create'))
            ->assertOk()
            ->assertSee('Option City 501');
    }

    public function test_relation_options_only_show_active_non_deprecated_records(): void
    {
        $province = $this->province();
        $this->city($province, 'admin.city.active', 'Visible Active City');
        $this->city($province, 'admin.city.inactive', 'Hidden Inactive City', isActive: false);
        $this->city($province, 'admin.city.deprecated', 'Hidden Deprecated City', deprecated: true);

        $this->get(route('iran-locations.admin.city-regions.create'))
            ->assertOk()
            ->assertSee('Visible Active City')
            ->assertDontSee('Hidden Inactive City')
            ->assertDontSee('Hidden Deprecated City');
    }

    public function test_alias_index_search_normalizes_query_term(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());
        $province = $this->province();
        $alias = new LocationAlias;

        DB::table($alias->getTable())->insert([
            'location_type' => Province::class,
            'location_id' => $province->getKey(),
            'alias' => 'Totally Different Alias',
            'normalized_alias' => 'normalized:needle',
            'source' => 'custom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('iran-locations.admin.aliases.index', ['q' => 'needle']))
            ->assertOk()
            ->assertSee('Totally Different Alias');
    }

    private function province(): Province
    {
        $province = new Province([
            'code' => 'admin.options.province',
            'name_fa' => 'Options Province',
            'normalized_name' => 'options province',
            'source' => 'custom',
        ]);
        $province->save();

        return $province;
    }

    private function city(
        Province $province,
        string $code,
        string $name,
        bool $isActive = true,
        bool $deprecated = false,
    ): City {
        $city = new City([
            'province_id' => $province->getKey(),
            'code' => $code,
            'name_fa' => $name,
            'normalized_name' => strtolower($name),
            'source' => 'custom',
            'is_active' => $isActive,
            'deprecated_at' => $deprecated ? now() : null,
        ]);
        $city->save();

        return $city;
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
                return 'normalized:'.$value;
            }

            public function slug(string $value): string
            {
                return 'slug:'.$value;
            }
        };
    }
}
