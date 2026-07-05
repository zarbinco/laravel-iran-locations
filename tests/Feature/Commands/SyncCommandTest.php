<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Commands;

use Illuminate\Support\Facades\Artisan;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;
use Zarbin\IranLocations\Tests\TestCase;

class SyncCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_sync_dry_run_prints_summary_and_leaves_database_empty(): void
    {
        $exitCode = Artisan::call('iran-locations:sync', ['--dry-run' => true]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Mode: dry-run', $output);
        self::assertStringContainsString('provinces: +31', $output);
        self::assertStringContainsString('counties: +484', $output);
        self::assertStringContainsString('official_districts: +1087', $output);
        self::assertStringContainsString('rural_districts: +73', $output);
        self::assertStringContainsString('cities: +1456', $output);
        self::assertStringContainsString('city_regions: +22', $output);
        self::assertStringContainsString('neighborhoods: +568', $output);
        self::assertStringContainsString('neighborhood_region: +568', $output);
        self::assertStringContainsString('No database changes were made.', $output);
        self::assertSame(0, Province::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());
    }

    public function test_sync_command_accepts_active_chunk_option(): void
    {
        $exitCode = Artisan::call('iran-locations:sync', ['--dry-run' => true, '--chunk' => 1]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Mode: dry-run', $output);
        self::assertStringContainsString('provinces: +31', $output);
        self::assertStringContainsString('No database changes were made.', $output);
        self::assertSame(0, Province::query()->count());
    }

    public function test_sync_applies_records_and_prints_summary(): void
    {
        $exitCode = Artisan::call('iran-locations:sync');
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Mode: apply', $output);
        self::assertStringContainsString('provinces: +31', $output);
        self::assertStringContainsString('counties: +484', $output);
        self::assertStringContainsString('official_districts: +1087', $output);
        self::assertStringContainsString('rural_districts: +73', $output);
        self::assertStringContainsString('cities: +1456', $output);
        self::assertStringContainsString('Database changes were applied safely.', $output);
        self::assertSame(31, Province::query()->count());
        self::assertSame(484, County::query()->count());
        self::assertSame(1087, OfficialDistrict::query()->count());
        self::assertSame(73, RuralDistrict::query()->count());
        self::assertSame(1456, City::query()->count());
        self::assertSame(22, CityRegion::query()->count());
        self::assertSame(0, CityArea::query()->count());
        self::assertSame(568, Neighborhood::query()->count());
        self::assertSame(1, LocationDataVersion::query()->count());

        $tehran = City::query()->where('code', 'ir.city.001.001.001.001')->firstOrFail();
        $region5 = CityRegion::query()->where('code', 'ir.city.tehran.region.05')->firstOrFail();

        self::assertSame(22, CityRegion::query()->forCityCode((string) $tehran->getAttribute('code'))->orderedByNumber()->count());
        self::assertGreaterThan(0, Neighborhood::query()->forRegionCode((string) $region5->getAttribute('code'))->count());
        self::assertSame(568, Neighborhood::query()->forCityCode((string) $tehran->getAttribute('code'))->ordered()->count());
    }

    public function test_status_after_sync_shows_latest_applied_version(): void
    {
        Artisan::call('iran-locations:sync');
        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('Database tables: ready', $output);
        self::assertStringContainsString('database cities: 1456', $output);
        self::assertStringContainsString('database package active cities: 1456', $output);
        self::assertStringContainsString('Latest applied database data version: 0.2.0-dev', $output);
        self::assertStringContainsString('Database appears synced: yes', $output);
    }

    public function test_status_remains_synced_after_custom_records_are_added(): void
    {
        Artisan::call('iran-locations:sync');

        $province = Province::query()->create([
            'code' => 'custom.province.status',
            'name_fa' => 'Custom Province',
            'normalized_name' => 'custom province',
            'source' => 'custom',
        ]);
        $city = City::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'custom.city.status',
            'name_fa' => 'Custom City',
            'normalized_name' => 'custom city',
            'source' => 'custom',
        ]);
        Neighborhood::query()->create([
            'city_id' => $city->getKey(),
            'code' => 'custom.neighborhood.status',
            'name_fa' => 'Custom Neighborhood',
            'normalized_name' => 'custom neighborhood',
            'source' => 'custom',
        ]);

        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('database provinces: 32', $output);
        self::assertStringContainsString('database package active provinces: 31', $output);
        self::assertStringContainsString('Database appears synced: yes', $output);
    }

    public function test_status_remains_synced_after_missing_package_record_is_deprecated(): void
    {
        Artisan::call('iran-locations:sync');

        Province::query()->create([
            'code' => 'ir.province.stale-status',
            'name_fa' => 'Stale Package Province',
            'normalized_name' => 'stale package province',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        Artisan::call('iran-locations:sync', ['--only' => 'provinces']);
        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('database provinces: 32', $output);
        self::assertStringContainsString('database package active provinces: 31', $output);
        self::assertStringContainsString('Database appears synced: yes', $output);
    }

    public function test_status_ignores_non_authoritative_empty_datasets_for_sync_detection(): void
    {
        Artisan::call('iran-locations:sync');

        $region = CityRegion::query()->where('code', 'ir.city.tehran.region.01')->firstOrFail();

        CityArea::query()->create([
            'city_region_id' => $region->getKey(),
            'code' => 'ir.city-area.local-status',
            'number' => 1,
            'name_fa' => 'Local Area',
            'normalized_name' => 'local area',
            'source' => 'package',
            'data_version' => 'local',
        ]);

        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('database city_areas: 1', $output);
        self::assertStringContainsString('database package active city_areas: 1', $output);
        self::assertStringContainsString('Database appears synced: yes', $output);
    }

    public function test_status_is_not_synced_when_authoritative_package_active_count_is_wrong(): void
    {
        Artisan::call('iran-locations:sync');

        City::query()->where('code', 'ir.city.001.001.001.001')->update([
            'is_active' => false,
        ]);

        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('database package active cities: 1455', $output);
        self::assertStringContainsString('Database appears synced: no', $output);
    }

    public function test_status_is_not_synced_without_latest_applied_version(): void
    {
        Artisan::call('iran-locations:status');
        $output = Artisan::output();

        self::assertStringContainsString('Latest applied database data version: none', $output);
        self::assertStringContainsString('Database appears synced: no', $output);
    }

    public function test_install_does_not_sync_unless_requested(): void
    {
        $exitCode = Artisan::call('iran-locations:install');
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('No database records were created or modified.', $output);
        self::assertSame(0, Province::query()->count());
    }

    public function test_install_with_sync_runs_safe_sync(): void
    {
        $exitCode = Artisan::call('iran-locations:install', ['--sync' => true]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Sync summary: +4289', $output);
        self::assertSame(31, Province::query()->count());
        self::assertSame(1, LocationDataVersion::query()->count());
    }
}
