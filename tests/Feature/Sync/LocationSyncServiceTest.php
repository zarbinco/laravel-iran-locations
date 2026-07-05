<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Sync;

use Carbon\CarbonImmutable;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\County;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\OfficialDistrict;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Models\RuralDistrict;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;
use Zarbin\IranLocations\Sync\LocationSyncException;
use Zarbin\IranLocations\Sync\LocationSyncOptions;
use Zarbin\IranLocations\Sync\LocationSyncService;
use Zarbin\IranLocations\Tests\TestCase;

class LocationSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_dry_run_reports_creates_without_writing_database_records(): void
    {
        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(dryRun: true));
        $datasets = $result->datasetsByName();

        self::assertTrue($result->dryRun);
        self::assertTrue($result->hasChanges());
        self::assertTrue($result->isSuccessful());
        self::assertSame(31, $datasets['provinces']->totals()['created']);
        self::assertSame(484, $datasets['counties']->totals()['created']);
        self::assertSame(1087, $datasets['official_districts']->totals()['created']);
        self::assertSame(73, $datasets['rural_districts']->totals()['created']);
        self::assertSame(1456, $datasets['cities']->totals()['created']);
        self::assertSame(22, $datasets['city_regions']->totals()['created']);
        self::assertSame(568, $datasets['neighborhoods']->totals()['created']);
        self::assertSame(568, $datasets['neighborhood_region']->totals()['created']);
        self::assertSame(0, Province::query()->count());
        self::assertSame(0, City::query()->count());
        self::assertSame(0, Neighborhood::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());
    }

    public function test_real_sync_creates_package_records_relationships_and_data_version(): void
    {
        $result = $this->app->make(LocationSyncService::class)->sync();

        self::assertTrue($result->isSuccessful());
        self::assertSame(31, Province::query()->count());
        self::assertSame(484, County::query()->count());
        self::assertSame(1087, OfficialDistrict::query()->count());
        self::assertSame(73, RuralDistrict::query()->count());
        self::assertSame(1456, City::query()->count());
        self::assertSame(22, CityRegion::query()->count());
        self::assertSame(568, Neighborhood::query()->count());
        self::assertSame(1, LocationDataVersion::query()->count());
        self::assertSame('0.2.0-dev', LocationDataVersion::latestAppliedVersion());
        self::assertSame(0, City::query()->whereDoesntHave('province')->count());
        self::assertSame(0, City::query()->whereDoesntHave('county')->count());
        self::assertSame(0, City::query()->whereDoesntHave('officialDistrict')->count());
        self::assertSame(0, Neighborhood::query()->whereDoesntHave('city')->count());

        $city = City::query()->where('code', 'ir.city.001.001.001.001')->firstOrFail();
        $province = Province::query()->where('code', 'ir.province.001')->firstOrFail();
        $county = County::query()->where('code', 'ir.county.001.001')->firstOrFail();
        $officialDistrict = OfficialDistrict::query()->where('code', 'ir.official_district.001.001.001')->firstOrFail();

        self::assertSame($province->getKey(), $city->getAttribute('province_id'));
        self::assertSame($county->getKey(), $city->getAttribute('county_id'));
        self::assertSame($officialDistrict->getKey(), $city->getAttribute('official_district_id'));
        self::assertSame('package', $city->getAttribute('source'));
        self::assertSame('0.2.0-dev', $city->getAttribute('data_version'));
        self::assertNotEmpty($city->getAttribute('normalized_name'));
    }

    public function test_second_sync_is_idempotent(): void
    {
        $service = $this->app->make(LocationSyncService::class);

        $service->sync();
        $firstVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $firstVersion);

        $result = $service->sync();
        $secondVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $secondVersion);

        $totals = $result->totals();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->hasChanges());
        self::assertSame(0, $totals['created']);
        self::assertSame(0, $totals['updated']);
        self::assertSame(0, $totals['deprecated']);
        self::assertSame(1, LocationDataVersion::query()->count());
        self::assertSame($firstVersion->getKey(), $secondVersion->getKey());
        self::assertNotSame('', $secondVersion->getAttribute('checksum'));
        self::assertNotEmpty($secondVersion->getAttribute('summary'));
    }

    public function test_custom_records_are_preserved_and_not_overwritten(): void
    {
        Province::query()->create([
            'code' => 'ir.province.001',
            'name_fa' => 'Custom Tehran',
            'normalized_name' => 'custom-tehran',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        Province::query()->create([
            'code' => 'custom.province.keep',
            'name_fa' => 'Keep Me',
            'normalized_name' => 'keep me',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $province = Province::query()->where('code', 'ir.province.001')->firstOrFail();

        self::assertSame(1, $result->datasetsByName()['provinces']->totals()['skipped']);
        self::assertSame('Custom Tehran', $province->getAttribute('name_fa'));
        self::assertSame('custom', $province->getAttribute('source'));
        self::assertTrue(Province::query()->where('code', 'custom.province.keep')->exists());
    }

    public function test_custom_official_hierarchy_records_are_preserved_and_not_overwritten(): void
    {
        $province = Province::query()->create([
            'code' => 'ir.province.001',
            'name_fa' => 'Custom Tehran',
            'normalized_name' => 'custom tehran',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        $county = County::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'ir.county.001.001',
            'name_fa' => 'Custom County',
            'normalized_name' => 'custom county',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        $officialDistrict = OfficialDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'ir.official_district.001.001.001',
            'name_fa' => 'Custom Official District',
            'normalized_name' => 'custom official district',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        RuralDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
            'code' => 'ir.rural_district.001.001.001.001',
            'name_fa' => 'Custom Rural District',
            'normalized_name' => 'custom rural district',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync();

        self::assertSame(1, $result->datasetsByName()['counties']->totals()['skipped']);
        self::assertSame(1, $result->datasetsByName()['official_districts']->totals()['skipped']);
        self::assertSame(1, $result->datasetsByName()['rural_districts']->totals()['skipped']);
        self::assertSame('Custom County', County::query()->where('code', 'ir.county.001.001')->firstOrFail()->getAttribute('name_fa'));
        self::assertSame('Custom Official District', OfficialDistrict::query()->where('code', 'ir.official_district.001.001.001')->firstOrFail()->getAttribute('name_fa'));
        self::assertSame('Custom Rural District', RuralDistrict::query()->where('code', 'ir.rural_district.001.001.001.001')->firstOrFail()->getAttribute('name_fa'));
    }

    public function test_missing_package_records_are_deprecated_by_default(): void
    {
        $stale = Province::query()->create([
            'code' => 'ir.province.stale',
            'name_fa' => 'Stale',
            'normalized_name' => 'stale',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $stale->refresh();

        self::assertSame(1, $result->datasetsByName()['provinces']->totals()['deprecated']);
        self::assertFalse((bool) $stale->getAttribute('is_active'));
        self::assertNotNull($stale->getAttribute('deprecated_at'));
    }

    public function test_missing_package_county_records_are_deprecated_by_default(): void
    {
        $province = Province::query()->create([
            'code' => 'ir.province.001',
            'name_fa' => 'Tehran',
            'normalized_name' => 'tehran',
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $stale = County::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'ir.county.stale',
            'name_fa' => 'Stale County',
            'normalized_name' => 'stale county',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['counties']));
        $stale->refresh();

        self::assertSame(1, $result->datasetsByName()['counties']->totals()['deprecated']);
        self::assertFalse((bool) $stale->getAttribute('is_active'));
        self::assertNotNull($stale->getAttribute('deprecated_at'));
    }

    public function test_no_deprecate_option_preserves_missing_package_records(): void
    {
        $stale = Province::query()->create([
            'code' => 'ir.province.stale',
            'name_fa' => 'Stale',
            'normalized_name' => 'stale',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(
            datasets: ['provinces'],
            deprecateMissing: false,
        ));
        $stale->refresh();

        self::assertSame(0, $result->datasetsByName()['provinces']->totals()['deprecated']);
        self::assertTrue((bool) $stale->getAttribute('is_active'));
        self::assertNull($stale->getAttribute('deprecated_at'));
    }

    public function test_empty_datasets_do_not_deprecate_by_default_but_can_when_explicitly_synced(): void
    {
        $service = $this->app->make(LocationSyncService::class);

        $service->sync();

        $region = CityRegion::query()->where('code', 'ir.city.tehran.region.01')->firstOrFail();
        $area = CityArea::query()->create([
            'city_region_id' => $region->getKey(),
            'code' => 'ir.city-area.stale',
            'number' => 1,
            'name_fa' => 'Stale Area',
            'normalized_name' => 'stale area',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        $service->sync();
        $area->refresh();

        self::assertTrue((bool) $area->getAttribute('is_active'));
        self::assertNull($area->getAttribute('deprecated_at'));

        $result = $service->sync(LocationSyncOptions::make(datasets: ['city_areas']));
        $area->refresh();

        self::assertSame(1, $result->datasetsByName()['city_areas']->totals()['deprecated']);
        self::assertFalse((bool) $area->getAttribute('is_active'));
        self::assertNotNull($area->getAttribute('deprecated_at'));
    }

    public function test_package_updates_preserve_display_override_and_restore_deprecated_records(): void
    {
        Province::query()->create([
            'code' => 'ir.province.001',
            'name_fa' => 'Old Tehran',
            'normalized_name' => 'old-tehran',
            'display_name_fa' => 'My Tehran',
            'source' => 'package',
            'data_version' => 'old',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-01-01'),
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $province = Province::query()->where('code', 'ir.province.001')->firstOrFail();

        self::assertGreaterThan(0, $result->datasetsByName()['provinces']->totals()['updated']);
        self::assertSame('تهران', $province->getAttribute('name_fa'));
        self::assertSame('My Tehran', $province->getAttribute('display_name_fa'));
        self::assertTrue((bool) $province->getAttribute('is_active'));
        self::assertNull($province->getAttribute('deprecated_at'));
    }

    public function test_dependency_failures_are_reported_without_creating_orphans(): void
    {
        $repository = new ArrayLocationDataRepository([
            'provinces' => [],
            'counties' => [],
            'official_districts' => [],
            'rural_districts' => [],
            'cities' => [[
                'code' => 'ir.city.missing-parent',
                'province_code' => 'ir.province.missing',
                'name_fa' => 'Missing Parent',
                'normalized_name' => 'missing parent',
                'slug' => 'missing-parent',
                'source' => 'package',
                'source_version' => 'test',
                'data_version' => 'test',
            ]],
            'city_regions' => [],
            'city_areas' => [],
            'neighborhoods' => [],
            'neighborhood_region' => [],
            'aliases' => [],
        ]);
        $service = $this->serviceFor($repository);

        $result = $service->sync(LocationSyncOptions::make(datasets: ['cities']));

        self::assertFalse($result->isSuccessful());
        self::assertSame(1, $result->datasetsByName()['cities']->totals()['failed']);
        self::assertSame(0, City::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());
    }

    public function test_sync_alias_payload_stores_stable_location_type_and_lifecycle_fields(): void
    {
        $repository = new ArrayLocationDataRepository([
            'provinces' => [[
                'code' => 'ir.province.alias-sync',
                'name_fa' => 'Alias Sync Province',
                'source_version' => 'source-province',
            ]],
            'aliases' => [[
                'location_type' => 'provinces',
                'location_code' => 'ir.province.alias-sync',
                'alias' => 'Alias Sync Province Alias',
                'normalized_alias' => 'alias sync province alias',
                'source_version' => 'source-alias',
            ]],
        ]);
        $service = $this->serviceFor($repository);

        $result = $service->sync(LocationSyncOptions::make(datasets: ['provinces', 'aliases']));

        $alias = LocationAlias::query()->firstOrFail();

        self::assertTrue($result->isSuccessful());
        self::assertSame(1, $result->datasetsByName()['aliases']->totals()['created']);
        self::assertSame('province', $alias->getAttribute('location_type'));
        self::assertSame('package', $alias->getAttribute('source'));
        self::assertSame('source-alias', $alias->getAttribute('source_version'));
        self::assertSame('test', $alias->getAttribute('data_version'));
        self::assertTrue((bool) $alias->getAttribute('is_active'));
        self::assertNull($alias->getAttribute('deprecated_at'));
    }

    public function test_repeated_sync_with_missing_checksum_uses_empty_checksum_and_updates_same_data_version_row(): void
    {
        $repository = new ArrayLocationDataRepository([
            'provinces' => [],
        ], omitChecksum: true);
        $service = $this->serviceFor($repository);

        $service->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $firstVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $firstVersion);

        $service->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $secondVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $secondVersion);

        self::assertSame(1, LocationDataVersion::query()->count());
        self::assertSame($firstVersion->getKey(), $secondVersion->getKey());
        self::assertSame('', $secondVersion->getAttribute('checksum'));
        self::assertNotEmpty($secondVersion->getAttribute('summary'));
    }

    public function test_hard_delete_behavior_is_rejected(): void
    {
        config()->set('iran-locations.data.package_record_delete_behavior', 'delete');

        $this->expectException(LocationSyncException::class);
        $this->expectExceptionMessage('Hard delete behavior is not supported');

        $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
    }

    private function serviceFor(LocationDataRepository $repository): LocationSyncService
    {
        return new LocationSyncService(
            $repository,
            new PassingLocationDataValidator,
            $this->app->make(LocationNormalizer::class),
            $this->app->make(LocationDatabaseInspector::class),
        );
    }
}

class PassingLocationDataValidator extends LocationDataValidator
{
    public function __construct() {}

    public function validate(): array
    {
        return [
            'ok' => true,
            'errors' => [],
            'checks' => [],
        ];
    }
}

class ArrayLocationDataRepository implements LocationDataRepository
{
    /**
     * @param  array<string, array<int, array<string, mixed>>>  $datasets
     */
    public function __construct(
        private readonly array $datasets,
        private readonly array $manifestOverrides = [],
        private readonly bool $omitChecksum = false,
    ) {}

    public function manifest(): array
    {
        $counts = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $counts[$dataset] = count($this->all($dataset));
        }

        $manifest = [
            'data_version' => 'test',
            'country_code' => 'IR',
            'source' => [
                'name' => 'test',
                'version' => 'test',
            ],
            'contains' => [],
            'counts' => $counts,
            'checksum' => 'test-checksum',
        ];

        if ($this->omitChecksum) {
            unset($manifest['checksum']);
        }

        return array_replace_recursive($manifest, $this->manifestOverrides);
    }

    public function dataVersion(): string
    {
        return 'test';
    }

    public function provinces(): array
    {
        return $this->all('provinces');
    }

    public function counties(): array
    {
        return $this->all('counties');
    }

    public function officialDistricts(): array
    {
        return $this->all('official_districts');
    }

    public function ruralDistricts(): array
    {
        return $this->all('rural_districts');
    }

    public function cities(): array
    {
        return $this->all('cities');
    }

    public function cityRegions(): array
    {
        return $this->all('city_regions');
    }

    public function cityAreas(): array
    {
        return $this->all('city_areas');
    }

    public function neighborhoods(): array
    {
        return $this->all('neighborhoods');
    }

    public function neighborhoodRegion(): array
    {
        return $this->all('neighborhood_region');
    }

    public function aliases(): array
    {
        return $this->all('aliases');
    }

    public function count(string $dataset): int
    {
        return count($this->all($dataset));
    }

    public function all(string $dataset): array
    {
        return $this->datasets[$dataset] ?? [];
    }
}
