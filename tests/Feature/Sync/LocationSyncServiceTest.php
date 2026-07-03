<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Sync;

use Carbon\CarbonImmutable;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\LocationDataVersion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;
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
        self::assertSame(1226, $datasets['cities']->totals()['created']);
        self::assertSame(505, $datasets['neighborhoods']->totals()['created']);
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
        self::assertSame(1226, City::query()->count());
        self::assertSame(505, Neighborhood::query()->count());
        self::assertSame(1, LocationDataVersion::query()->count());
        self::assertSame('0.1.0-dev', LocationDataVersion::latestAppliedVersion());
        self::assertSame(0, City::query()->whereDoesntHave('province')->count());
        self::assertSame(0, Neighborhood::query()->whereDoesntHave('city')->count());

        $city = City::query()->where('code', 'ir.city.001.0001')->firstOrFail();
        $province = Province::query()->where('code', 'ir.province.001')->firstOrFail();

        self::assertSame($province->getKey(), $city->getAttribute('province_id'));
        self::assertSame('package', $city->getAttribute('source'));
        self::assertSame('0.1.0-dev', $city->getAttribute('data_version'));
        self::assertNotEmpty($city->getAttribute('normalized_name'));
    }

    public function test_second_sync_is_idempotent(): void
    {
        $service = $this->app->make(LocationSyncService::class);

        $service->sync();
        $result = $service->sync();
        $totals = $result->totals();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->hasChanges());
        self::assertSame(0, $totals['created']);
        self::assertSame(0, $totals['updated']);
        self::assertSame(0, $totals['deprecated']);
        self::assertSame(2, LocationDataVersion::query()->count());
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

        $city = City::query()->where('code', 'ir.city.001.0001')->firstOrFail();
        $region = CityRegion::query()->create([
            'city_id' => $city->getKey(),
            'code' => 'ir.city-region.stale',
            'number' => 1,
            'name_fa' => 'Stale Region',
            'normalized_name' => 'stale region',
            'source' => 'package',
            'data_version' => 'old',
        ]);

        $service->sync();
        $region->refresh();

        self::assertTrue((bool) $region->getAttribute('is_active'));
        self::assertNull($region->getAttribute('deprecated_at'));

        $result = $service->sync(LocationSyncOptions::make(datasets: ['city_regions']));
        $region->refresh();

        self::assertSame(1, $result->datasetsByName()['city_regions']->totals()['deprecated']);
        self::assertFalse((bool) $region->getAttribute('is_active'));
        self::assertNotNull($region->getAttribute('deprecated_at'));
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
            'aliases' => [],
        ]);
        $service = $this->serviceFor($repository);

        $result = $service->sync(LocationSyncOptions::make(datasets: ['cities']));

        self::assertFalse($result->isSuccessful());
        self::assertSame(1, $result->datasetsByName()['cities']->totals()['failed']);
        self::assertSame(0, City::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());
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
    ) {}

    public function manifest(): array
    {
        $counts = [];

        foreach (LocationDataManifest::datasets() as $dataset) {
            $counts[$dataset] = count($this->all($dataset));
        }

        return [
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
    }

    public function dataVersion(): string
    {
        return 'test';
    }

    public function provinces(): array
    {
        return $this->all('provinces');
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
