<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Sync;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
use Zarbin\IranLocations\Support\LocationModelResolver;
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

        $city = City::query()->where('code', 's.01.01.01.01')->firstOrFail();
        $province = Province::query()->where('code', 'p.01')->firstOrFail();
        $county = County::query()->where('code', 'c.01.01')->firstOrFail();
        $officialDistrict = OfficialDistrict::query()->where('code', 'b.01.01.01')->firstOrFail();

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

    public function test_chunk_size_one_full_sync_matches_default_dry_run_totals(): void
    {
        $service = $this->app->make(LocationSyncService::class);

        $default = $service->sync(LocationSyncOptions::make(dryRun: true));
        $chunked = $service->sync(LocationSyncOptions::make(dryRun: true, chunkSize: 1));

        self::assertTrue($chunked->isSuccessful());
        self::assertSame($default->totals(), $chunked->totals());
        self::assertSame($default->summary()['datasets'], $chunked->summary()['datasets']);
    }

    public function test_alias_sync_with_chunk_size_one_creates_aliases(): void
    {
        $records = $this->createSyncGraph('alias-chunk');
        $repository = new ArrayLocationDataRepository([
            'aliases' => [
                [
                    'location_type' => 'province',
                    'location_code' => $records['province']->getAttribute('code'),
                    'alias' => 'Chunk Alias One',
                    'normalized_alias' => 'chunk alias one',
                ],
                [
                    'location_type' => 'province',
                    'location_code' => $records['province']->getAttribute('code'),
                    'alias' => 'Chunk Alias Two',
                    'normalized_alias' => 'chunk alias two',
                ],
            ],
        ]);

        $result = $this->serviceFor($repository)->sync(LocationSyncOptions::make(datasets: ['aliases'], chunkSize: 1));

        self::assertTrue($result->isSuccessful());
        self::assertSame(2, $result->datasetsByName()['aliases']->totals()['created']);
        self::assertSame(2, LocationAlias::query()->where('location_type', 'province')->count());
    }

    public function test_neighborhood_region_sync_with_chunk_size_one_creates_mappings(): void
    {
        $first = $this->createSyncGraph('mapping-chunk-one');
        $second = $this->createSyncGraph('mapping-chunk-two');
        $repository = new ArrayLocationDataRepository([
            'neighborhood_region' => [
                [
                    'neighborhood_code' => $first['neighborhood']->getAttribute('code'),
                    'city_region_code' => $first['region']->getAttribute('code'),
                    'is_primary' => true,
                ],
                [
                    'neighborhood_code' => $second['neighborhood']->getAttribute('code'),
                    'city_region_code' => $second['region']->getAttribute('code'),
                    'is_primary' => false,
                ],
            ],
        ]);

        $result = $this->serviceFor($repository)->sync(LocationSyncOptions::make(datasets: ['neighborhood_region'], chunkSize: 1));

        self::assertTrue($result->isSuccessful());
        self::assertSame(2, $result->datasetsByName()['neighborhood_region']->totals()['created']);
        self::assertSame(2, DB::table(LocationModelResolver::table('neighborhood_region'))->where('source', 'package')->count());
    }

    public function test_custom_records_are_preserved_and_not_overwritten(): void
    {
        Province::query()->create([
            'code' => 'p.01',
            'name_fa' => 'Custom Tehran',
            'normalized_name' => 'custom-tehran',
            'display_name_fa' => 'Custom Tehran Display',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        Province::query()->create([
            'code' => 'x.p.keep',
            'name_fa' => 'Keep Me',
            'normalized_name' => 'keep me',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $province = Province::query()->where('code', 'p.01')->firstOrFail();

        self::assertSame(1, $result->datasetsByName()['provinces']->totals()['skipped']);
        self::assertSame('Custom Tehran', $province->getAttribute('name_fa'));
        self::assertSame('Custom Tehran Display', $province->getAttribute('display_name_fa'));
        self::assertSame('custom', $province->getAttribute('source'));
        self::assertTrue(Province::query()->where('code', 'x.p.keep')->exists());
    }

    public function test_custom_official_hierarchy_records_are_preserved_and_not_overwritten(): void
    {
        $province = Province::query()->create([
            'code' => 'p.01',
            'name_fa' => 'Custom Tehran',
            'normalized_name' => 'custom tehran',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        $county = County::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'c.01.01',
            'name_fa' => 'Custom County',
            'normalized_name' => 'custom county',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        $officialDistrict = OfficialDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'code' => 'b.01.01.01',
            'name_fa' => 'Custom Official District',
            'normalized_name' => 'custom official district',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);
        RuralDistrict::query()->create([
            'province_id' => $province->getKey(),
            'county_id' => $county->getKey(),
            'official_district_id' => $officialDistrict->getKey(),
            'code' => 'd.01.01.01.01',
            'name_fa' => 'Custom Rural District',
            'normalized_name' => 'custom rural district',
            'source' => 'custom',
            'data_version' => 'custom',
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync();

        self::assertSame(1, $result->datasetsByName()['counties']->totals()['skipped']);
        self::assertSame(1, $result->datasetsByName()['official_districts']->totals()['skipped']);
        self::assertSame(1, $result->datasetsByName()['rural_districts']->totals()['skipped']);
        self::assertSame('Custom County', County::query()->where('code', 'c.01.01')->firstOrFail()->getAttribute('name_fa'));
        self::assertSame('Custom Official District', OfficialDistrict::query()->where('code', 'b.01.01.01')->firstOrFail()->getAttribute('name_fa'));
        self::assertSame('Custom Rural District', RuralDistrict::query()->where('code', 'd.01.01.01.01')->firstOrFail()->getAttribute('name_fa'));
    }

    public function test_missing_package_records_are_deprecated_by_default(): void
    {
        $stale = Province::query()->create([
            'code' => 'x.p.stale',
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
            'code' => 'p.01',
            'name_fa' => 'Tehran',
            'normalized_name' => 'tehran',
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $stale = County::query()->create([
            'province_id' => $province->getKey(),
            'code' => 'x.c.stale',
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
            'code' => 'x.p.stale',
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

        $region = CityRegion::query()->where('code', 'r.01.01.01.01.01')->firstOrFail();
        $area = CityArea::query()->create([
            'city_region_id' => $region->getKey(),
            'code' => 'x.a.stale',
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

    public function test_package_updates_replace_package_owned_display_name_and_restore_deprecated_records(): void
    {
        Province::query()->create([
            'code' => 'p.01',
            'name_fa' => 'Old Tehran',
            'normalized_name' => 'old-tehran',
            'display_name_fa' => 'My Tehran',
            'source' => 'package',
            'data_version' => 'old',
            'is_active' => false,
            'deprecated_at' => CarbonImmutable::parse('2026-01-01'),
        ]);

        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
        $province = Province::query()->where('code', 'p.01')->firstOrFail();

        self::assertGreaterThan(0, $result->datasetsByName()['provinces']->totals()['updated']);
        self::assertSame('تهران', $province->getAttribute('name_fa'));
        self::assertNull($province->getAttribute('display_name_fa'));
        self::assertTrue((bool) $province->getAttribute('is_active'));
        self::assertNull($province->getAttribute('deprecated_at'));
    }

    public function test_partial_sync_does_not_record_global_data_version(): void
    {
        $result = $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));

        self::assertTrue($result->isSuccessful());
        self::assertSame(31, Province::query()->count());
        self::assertSame(0, LocationDataVersion::query()->count());
        self::assertNull(LocationDataVersion::latestAppliedVersion());
    }

    public function test_dependency_failures_are_reported_without_creating_orphans(): void
    {
        $repository = new ArrayLocationDataRepository([
            'provinces' => [],
            'counties' => [],
            'official_districts' => [],
            'rural_districts' => [],
            'cities' => [[
                'code' => 'x.s.missing-parent',
                'province_code' => 'x.p.missing',
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
                'code' => 'x.p.alias-sync',
                'name_fa' => 'Alias Sync Province',
                'source_version' => 'source-province',
            ]],
            'aliases' => [[
                'location_type' => 'provinces',
                'location_code' => 'x.p.alias-sync',
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

    public function test_package_alias_missing_from_explicit_alias_dataset_is_deprecated(): void
    {
        $records = $this->createSyncGraph('alias-stale-package');
        $alias = $this->packageAlias($records['province'], 'Package Alias Missing');

        $result = $this->serviceFor(new ArrayLocationDataRepository(['aliases' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(1, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertFalse((bool) $alias->getAttribute('is_active'));
        self::assertNotNull($alias->getAttribute('deprecated_at'));
        self::assertSame('test', $alias->getAttribute('data_version'));
    }

    public function test_custom_alias_missing_from_explicit_alias_dataset_is_preserved(): void
    {
        $records = $this->createSyncGraph('alias-stale-custom');
        $alias = $this->packageAlias($records['province'], 'Custom Alias Missing', source: 'custom');

        $result = $this->serviceFor(new ArrayLocationDataRepository(['aliases' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(0, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertTrue((bool) $alias->getAttribute('is_active'));
        self::assertNull($alias->getAttribute('deprecated_at'));
        self::assertSame('custom', $alias->getAttribute('source'));
    }

    public function test_already_deprecated_stale_package_alias_reports_unchanged(): void
    {
        $records = $this->createSyncGraph('alias-stale-deprecated');
        $alias = $this->packageAlias($records['province'], 'Deprecated Alias Missing', isActive: false, deprecatedAt: now());

        $result = $this->serviceFor(new ArrayLocationDataRepository(['aliases' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(1, $result->datasetsByName()['aliases']->totals()['unchanged']);
        self::assertSame(0, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertFalse((bool) $alias->getAttribute('is_active'));
        self::assertNotNull($alias->getAttribute('deprecated_at'));
    }

    public function test_dry_run_reports_stale_alias_deprecation_without_mutating(): void
    {
        $records = $this->createSyncGraph('alias-stale-dry-run');
        $alias = $this->packageAlias($records['province'], 'Dry Run Alias Missing');

        $result = $this->serviceFor(new ArrayLocationDataRepository(['aliases' => []]))
            ->sync(LocationSyncOptions::make(dryRun: true, datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(1, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertTrue((bool) $alias->getAttribute('is_active'));
        self::assertNull($alias->getAttribute('deprecated_at'));
    }

    public function test_default_full_sync_with_empty_aliases_does_not_deprecate_package_aliases(): void
    {
        $records = $this->createSyncGraph('alias-default-empty');
        $alias = $this->packageAlias($records['province'], 'Default Empty Alias');

        $result = $this->serviceFor(new ArrayLocationDataRepository([]))->sync();

        $alias->refresh();

        self::assertSame(0, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertTrue((bool) $alias->getAttribute('is_active'));
        self::assertNull($alias->getAttribute('deprecated_at'));
    }

    public function test_explicit_empty_alias_dataset_deprecates_package_aliases(): void
    {
        $records = $this->createSyncGraph('alias-explicit-empty');
        $alias = $this->packageAlias($records['province'], 'Explicit Empty Alias');

        $result = $this->serviceFor(new ArrayLocationDataRepository([]))
            ->sync(LocationSyncOptions::make(datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(1, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertFalse((bool) $alias->getAttribute('is_active'));
        self::assertSame('province', $alias->getAttribute('location_type'));
    }

    public function test_alias_stale_policy_uses_stable_location_type_keys(): void
    {
        $records = $this->createSyncGraph('alias-stable-key');
        $alias = $this->packageAlias($records['province'], 'Stable Key Alias');
        $repository = new ArrayLocationDataRepository([
            'aliases' => [[
                'location_type' => 'provinces',
                'location_code' => $records['province']->getAttribute('code'),
                'alias' => 'Stable Key Alias',
                'normalized_alias' => $alias->getAttribute('normalized_alias'),
            ]],
        ]);

        $result = $this->serviceFor($repository)->sync(LocationSyncOptions::make(datasets: ['aliases']));

        $alias->refresh();

        self::assertSame(0, $result->datasetsByName()['aliases']->totals()['deprecated']);
        self::assertSame('province', $alias->getAttribute('location_type'));
        self::assertTrue((bool) $alias->getAttribute('is_active'));
        self::assertNull($alias->getAttribute('deprecated_at'));
    }

    public function test_repeated_sync_with_missing_checksum_uses_empty_checksum_and_updates_same_data_version_row(): void
    {
        $repository = new ArrayLocationDataRepository([
            'provinces' => [],
        ], omitChecksum: true);
        $service = $this->serviceFor($repository);

        $service->sync();
        $firstVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $firstVersion);

        $service->sync();
        $secondVersion = LocationDataVersion::query()->first();
        self::assertInstanceOf(LocationDataVersion::class, $secondVersion);

        self::assertSame(1, LocationDataVersion::query()->count());
        self::assertSame($firstVersion->getKey(), $secondVersion->getKey());
        self::assertSame('', $secondVersion->getAttribute('checksum'));
        self::assertNotEmpty($secondVersion->getAttribute('summary'));
    }

    public function test_package_neighborhood_region_mapping_missing_from_explicit_dataset_is_deprecated(): void
    {
        $records = $this->createSyncGraph('mapping-stale-package');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region']);

        $result = $this->serviceFor(new ArrayLocationDataRepository(['neighborhood_region' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(1, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertFalse((bool) $mapping->is_active);
        self::assertNotNull($mapping->deprecated_at);
        self::assertSame('test', $mapping->data_version);
    }

    public function test_custom_neighborhood_region_mapping_missing_from_explicit_dataset_is_preserved(): void
    {
        $records = $this->createSyncGraph('mapping-stale-custom');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region'], source: 'custom');

        $result = $this->serviceFor(new ArrayLocationDataRepository(['neighborhood_region' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(0, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertTrue((bool) $mapping->is_active);
        self::assertNull($mapping->deprecated_at);
        self::assertSame('custom', $mapping->source);
    }

    public function test_already_deprecated_stale_neighborhood_region_mapping_reports_unchanged(): void
    {
        $records = $this->createSyncGraph('mapping-stale-deprecated');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region'], isActive: false, deprecatedAt: now());

        $result = $this->serviceFor(new ArrayLocationDataRepository(['neighborhood_region' => []]))
            ->sync(LocationSyncOptions::make(datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(1, $result->datasetsByName()['neighborhood_region']->totals()['unchanged']);
        self::assertSame(0, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertFalse((bool) $mapping->is_active);
        self::assertNotNull($mapping->deprecated_at);
    }

    public function test_dry_run_reports_stale_neighborhood_region_mapping_deprecation_without_mutating(): void
    {
        $records = $this->createSyncGraph('mapping-stale-dry-run');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region']);

        $result = $this->serviceFor(new ArrayLocationDataRepository(['neighborhood_region' => []]))
            ->sync(LocationSyncOptions::make(dryRun: true, datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(1, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertTrue((bool) $mapping->is_active);
        self::assertNull($mapping->deprecated_at);
    }

    public function test_default_full_sync_with_empty_neighborhood_region_does_not_deprecate_package_mappings(): void
    {
        $records = $this->createSyncGraph('mapping-default-empty');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region']);

        $result = $this->serviceFor(new ArrayLocationDataRepository([]))->sync();
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(0, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertTrue((bool) $mapping->is_active);
        self::assertNull($mapping->deprecated_at);
    }

    public function test_explicit_empty_neighborhood_region_dataset_deprecates_package_mappings(): void
    {
        $records = $this->createSyncGraph('mapping-explicit-empty');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region']);

        $result = $this->serviceFor(new ArrayLocationDataRepository([]))
            ->sync(LocationSyncOptions::make(datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(1, $result->datasetsByName()['neighborhood_region']->totals()['deprecated']);
        self::assertFalse((bool) $mapping->is_active);
        self::assertNotNull($mapping->deprecated_at);
    }

    public function test_current_package_neighborhood_region_mapping_reactivates_deprecated_mapping(): void
    {
        $records = $this->createSyncGraph('mapping-reactivate');
        $this->insertNeighborhoodRegionMapping($records['neighborhood'], $records['region'], isActive: false, deprecatedAt: now(), dataVersion: 'old');
        $repository = new ArrayLocationDataRepository([
            'neighborhood_region' => [[
                'neighborhood_code' => $records['neighborhood']->getAttribute('code'),
                'city_region_code' => $records['region']->getAttribute('code'),
                'is_primary' => true,
            ]],
        ]);

        $result = $this->serviceFor($repository)->sync(LocationSyncOptions::make(datasets: ['neighborhood_region']));
        $mapping = $this->mappingRow($records['neighborhood'], $records['region']);

        self::assertSame(1, $result->datasetsByName()['neighborhood_region']->totals()['updated']);
        self::assertTrue((bool) $mapping->is_active);
        self::assertNull($mapping->deprecated_at);
        self::assertSame('test', $mapping->data_version);
    }

    public function test_hard_delete_behavior_is_rejected(): void
    {
        config()->set('iran-locations.data.package_record_delete_behavior', 'delete');

        $this->expectException(LocationSyncException::class);
        $this->expectExceptionMessage('Hard delete behavior is not supported');

        $this->app->make(LocationSyncService::class)->sync(LocationSyncOptions::make(datasets: ['provinces']));
    }

    /**
     * @return array{province: Province, city: City, region: CityRegion, neighborhood: Neighborhood}
     */
    private function createSyncGraph(string $suffix): array
    {
        $province = new Province([
            'code' => "x.p.{$suffix}",
            'name_fa' => "Province {$suffix}",
            'normalized_name' => "province {$suffix}",
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $province->save();

        $city = new City([
            'province_id' => $province->getKey(),
            'code' => "x.s.{$suffix}",
            'name_fa' => "City {$suffix}",
            'normalized_name' => "city {$suffix}",
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $city->save();

        $region = new CityRegion([
            'city_id' => $city->getKey(),
            'code' => "x.r.{$suffix}",
            'number' => 1,
            'name_fa' => "Region {$suffix}",
            'normalized_name' => "region {$suffix}",
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $region->save();

        $neighborhood = new Neighborhood([
            'city_id' => $city->getKey(),
            'default_city_region_id' => $region->getKey(),
            'code' => "x.n.{$suffix}",
            'name_fa' => "Neighborhood {$suffix}",
            'normalized_name' => "neighborhood {$suffix}",
            'source' => 'package',
            'data_version' => 'old',
        ]);
        $neighborhood->save();

        return [
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'neighborhood' => $neighborhood,
        ];
    }

    private function packageAlias(
        Province $province,
        string $alias,
        string $source = 'package',
        bool $isActive = true,
        mixed $deprecatedAt = null,
    ): LocationAlias {
        $model = $province->aliases()->create([
            'alias' => $alias,
            'normalized_alias' => strtolower($alias),
            'source' => $source,
            'data_version' => 'old',
            'is_active' => $isActive,
            'deprecated_at' => $deprecatedAt,
        ]);
        self::assertInstanceOf(LocationAlias::class, $model);

        return $model;
    }

    private function insertNeighborhoodRegionMapping(
        Neighborhood $neighborhood,
        CityRegion $region,
        string $source = 'package',
        bool $isActive = true,
        mixed $deprecatedAt = null,
        string $dataVersion = 'old',
    ): void {
        DB::table(LocationModelResolver::table('neighborhood_region'))->insert([
            'neighborhood_id' => $neighborhood->getKey(),
            'city_region_id' => $region->getKey(),
            'is_primary' => true,
            'source' => $source,
            'is_active' => $isActive,
            'source_version' => 'old-source',
            'data_version' => $dataVersion,
            'deprecated_at' => $deprecatedAt,
            'confidence' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mappingRow(Neighborhood $neighborhood, CityRegion $region): object
    {
        $mapping = DB::table(LocationModelResolver::table('neighborhood_region'))
            ->where('neighborhood_id', $neighborhood->getKey())
            ->where('city_region_id', $region->getKey())
            ->first();

        self::assertIsObject($mapping);

        return $mapping;
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
