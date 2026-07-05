<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Zarbin\IranLocations\Tests\TestCase;

class MigrationTest extends TestCase
{
    public function test_migrations_can_run(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');

        $this->artisan('migrate')->run();

        foreach (config('iran-locations.tables') as $table) {
            self::assertTrue(Schema::hasTable($table), "Table [{$table}] was not created.");
        }

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.provinces'), [
            'id',
            'code',
            'name_fa',
            'slug',
            'normalized_name',
            'is_active',
            'source',
            'data_version',
            'deprecated_at',
            'replaced_by_id',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.counties'), [
            'id',
            'province_id',
            'code',
            'name_fa',
            'normalized_name',
            'is_active',
            'source',
            'replaced_by_id',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.official_districts'), [
            'id',
            'province_id',
            'county_id',
            'code',
            'name_fa',
            'normalized_name',
            'is_active',
            'source',
            'replaced_by_id',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.rural_districts'), [
            'id',
            'province_id',
            'county_id',
            'official_district_id',
            'code',
            'name_fa',
            'normalized_name',
            'is_active',
            'source',
            'replaced_by_id',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.cities'), [
            'province_id',
            'county_id',
            'official_district_id',
            'code',
            'name_fa',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.neighborhoods'), [
            'city_id',
            'default_city_region_id',
            'default_city_area_id',
            'type',
            'latitude',
            'longitude',
        ]));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.neighborhood_region'), [
            'neighborhood_id',
            'city_region_id',
            'is_primary',
            'source',
            'is_active',
            'source_version',
            'data_version',
            'deprecated_at',
            'confidence',
        ]));
        self::assertFalse(Schema::hasColumn(config('iran-locations.tables.neighborhood_region'), 'replaced_by_id'));

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.location_aliases'), [
            'location_type',
            'location_id',
            'alias',
            'normalized_alias',
            'is_active',
            'source',
            'source_version',
            'data_version',
            'deprecated_at',
        ]));
        self::assertFalse(Schema::hasColumn(config('iran-locations.tables.location_aliases'), 'replaced_by_id'));

        self::assertFalse(Schema::hasTable('iran_districts'));
    }
}
