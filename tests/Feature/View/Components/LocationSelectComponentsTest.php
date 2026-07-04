<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\View\Components;

use Illuminate\Support\Facades\Blade;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\Support\CreatesLocationTestData;
use Zarbin\IranLocations\Tests\TestCase;

class LocationSelectComponentsTest extends TestCase
{
    use CreatesLocationTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindFakeLocationNormalizer();
        $this->loadMigrationsFrom(dirname(__DIR__, 4).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_province_select_renders_attributes_and_active_options(): void
    {
        $records = $this->createLocationGraph('component-province');
        Province::create([
            'code' => 'province-component-inactive',
            'name_fa' => 'Inactive Province',
            'is_active' => false,
        ]);

        $html = Blade::render('<x-iran-locations::province-select name="province_id" placeholder="Choose" class="iran-select" required disabled />');

        self::assertStringContainsString('name="province_id"', $html);
        self::assertStringContainsString('Choose', $html);
        self::assertStringContainsString('iran-select', $html);
        self::assertStringContainsString('required', $html);
        self::assertStringContainsString('disabled', $html);
        self::assertStringContainsString('Province component-province', $html);
        self::assertStringContainsString('data-code="'.$records['province']->getAttribute('code').'"', $html);
        self::assertStringNotContainsString('Inactive Province', $html);
    }

    public function test_city_select_filters_by_province_and_uses_selected_value(): void
    {
        $records = $this->createLocationGraph('component-city');
        $other = $this->createLocationGraph('component-city-other');

        $html = Blade::render(
            '<x-iran-locations::city-select name="city_id" :province-id="$provinceId" :selected="$selected" />',
            ['provinceId' => $records['province']->getKey(), 'selected' => $records['city']->getKey()],
        );

        self::assertStringContainsString('City component-city', $html);
        self::assertStringNotContainsString('City component-city-other', $html);
        self::assertMatchesRegularExpression('/value="'.$records['city']->getKey().'"[^>]*selected/', $html);

        $byCode = Blade::render(
            '<x-iran-locations::city-select name="city_id" :province-code="$provinceCode" />',
            ['provinceCode' => $other['province']->getAttribute('code')],
        );

        self::assertStringContainsString('City component-city-other', $byCode);
        self::assertStringNotContainsString('data-code="'.$records['city']->getAttribute('code').'"', $byCode);
    }

    public function test_region_area_and_neighborhood_selects_apply_parent_filters(): void
    {
        $records = $this->createLocationGraph('component-filters');
        $this->createLocationGraph('component-filters-other');

        $regions = Blade::render(
            '<x-iran-locations::city-region-select name="region_id" :city-id="$cityId" />',
            ['cityId' => $records['city']->getKey()],
        );
        self::assertStringContainsString('Region component-filters', $regions);
        self::assertStringNotContainsString('Region component-filters-other', $regions);

        $areas = Blade::render(
            '<x-iran-locations::city-area-select name="area_id" :city-region-id="$regionId" />',
            ['regionId' => $records['region']->getKey()],
        );
        self::assertStringContainsString('Area component-filters', $areas);
        self::assertStringNotContainsString('Area component-filters-other', $areas);

        $neighborhoods = Blade::render(
            '<x-iran-locations::neighborhood-select name="neighborhood_id" :city-id="$cityId" :city-region-id="$regionId" type="neighborhood" />',
            ['cityId' => $records['city']->getKey(), 'regionId' => $records['region']->getKey()],
        );
        self::assertStringContainsString('Neighborhood component-filters', $neighborhoods);
        self::assertStringNotContainsString('Neighborhood component-filters-other', $neighborhoods);
    }

    public function test_official_division_selects_apply_parent_filters(): void
    {
        $records = $this->createLocationGraph('component-official');
        $other = $this->createLocationGraph('component-official-other');

        $counties = Blade::render(
            '<x-iran-locations::county-select name="county_id" :province-id="$provinceId" placeholder="Choose county" required class="county-select" />',
            ['provinceId' => $records['province']->getKey()],
        );

        self::assertStringContainsString('Choose county', $counties);
        self::assertStringContainsString('required', $counties);
        self::assertStringContainsString('county-select', $counties);
        self::assertStringContainsString('County component-official', $counties);
        self::assertStringNotContainsString('County component-official-other', $counties);

        $countiesByCode = Blade::render(
            '<x-iran-locations::county-select name="county_id" :province-code="$provinceCode" />',
            ['provinceCode' => $other['province']->getAttribute('code')],
        );

        self::assertStringContainsString('County component-official-other', $countiesByCode);
        self::assertStringNotContainsString('data-code="'.$records['county']->getAttribute('code').'"', $countiesByCode);

        $officialDistricts = Blade::render(
            '<x-iran-locations::official-district-select name="official_district_id" :county-id="$countyId" :selected="$selected" />',
            ['countyId' => $records['county']->getKey(), 'selected' => $records['officialDistrict']->getKey()],
        );

        self::assertStringContainsString('Official District component-official', $officialDistricts);
        self::assertStringNotContainsString('Official District component-official-other', $officialDistricts);
        self::assertMatchesRegularExpression('/value="'.$records['officialDistrict']->getKey().'"[^>]*selected/', $officialDistricts);

        $officialDistrictsByCode = Blade::render(
            '<x-iran-locations::official-district-select name="official_district_id" :county-code="$countyCode" />',
            ['countyCode' => $other['county']->getAttribute('code')],
        );

        self::assertStringContainsString('Official District component-official-other', $officialDistrictsByCode);

        $ruralDistricts = Blade::render(
            '<x-iran-locations::rural-district-select name="rural_district_id" :official-district-id="$officialDistrictId" />',
            ['officialDistrictId' => $records['officialDistrict']->getKey()],
        );

        self::assertStringContainsString('Rural District component-official', $ruralDistricts);
        self::assertStringNotContainsString('Rural District component-official-other', $ruralDistricts);

        $ruralDistrictsByCode = Blade::render(
            '<x-iran-locations::rural-district-select name="rural_district_id" :official-district-code="$officialDistrictCode" />',
            ['officialDistrictCode' => $other['officialDistrict']->getAttribute('code')],
        );

        self::assertStringContainsString('Rural District component-official-other', $ruralDistrictsByCode);
    }

    public function test_city_select_filters_by_county_and_official_district(): void
    {
        $records = $this->createLocationGraph('component-city-official');
        $other = $this->createLocationGraph('component-city-official-other');

        $byCounty = Blade::render(
            '<x-iran-locations::city-select name="city_id" :county-id="$countyId" />',
            ['countyId' => $records['county']->getKey()],
        );

        self::assertStringContainsString('City component-city-official', $byCounty);
        self::assertStringNotContainsString('City component-city-official-other', $byCounty);

        $byOfficialDistrict = Blade::render(
            '<x-iran-locations::city-select name="city_id" :official-district-id="$officialDistrictId" />',
            ['officialDistrictId' => $records['officialDistrict']->getKey()],
        );

        self::assertStringContainsString('City component-city-official', $byOfficialDistrict);
        self::assertStringNotContainsString('City component-city-official-other', $byOfficialDistrict);

        $byOfficialDistrictCode = Blade::render(
            '<x-iran-locations::city-select name="city_id" :official-district-code="$officialDistrictCode" />',
            ['officialDistrictCode' => $other['officialDistrict']->getAttribute('code')],
        );

        self::assertStringContainsString('City component-city-official-other', $byOfficialDistrictCode);
        self::assertStringNotContainsString('City component-city-official"', $byOfficialDistrictCode);
    }

    public function test_components_preserve_old_input_and_skip_inactive_or_deprecated_records(): void
    {
        $records = $this->createLocationGraph('component-old');
        $inactive = City::create([
            'province_id' => $records['province']->getKey(),
            'code' => 'city-component-inactive',
            'name_fa' => 'Inactive Component City',
            'is_active' => false,
        ]);
        $deprecated = City::create([
            'province_id' => $records['province']->getKey(),
            'code' => 'city-component-deprecated',
            'name_fa' => 'Deprecated Component City',
            'is_active' => false,
            'deprecated_at' => now(),
        ]);

        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app['session.store']->put('_old_input', [
            'city_id' => (string) $records['city']->getKey(),
        ]);

        $html = Blade::render(
            '<x-iran-locations::city-select name="city_id" :province-id="$provinceId" selected="999" />',
            ['provinceId' => $records['province']->getKey()],
        );

        self::assertMatchesRegularExpression('/value="'.$records['city']->getKey().'"[^>]*selected/', $html);
        self::assertStringNotContainsString((string) $inactive->getAttribute('name_fa'), $html);
        self::assertStringNotContainsString((string) $deprecated->getAttribute('name_fa'), $html);
    }

    public function test_city_select_has_no_arbitrary_500_option_limit(): void
    {
        $province = Province::create([
            'code' => 'province-component-many',
            'name_fa' => 'Province Many',
        ]);

        for ($i = 1; $i <= 501; $i++) {
            City::create([
                'province_id' => $province->getKey(),
                'code' => 'city-component-many-'.$i,
                'name_fa' => 'City Many '.$i,
            ]);
        }

        $html = Blade::render(
            '<x-iran-locations::city-select name="city_id" :province-id="$provinceId" />',
            ['provinceId' => $province->getKey()],
        );

        self::assertStringContainsString('City Many 501', $html);
    }
}
