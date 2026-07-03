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

        self::assertTrue(Schema::hasColumns(config('iran-locations.tables.neighborhoods'), [
            'city_id',
            'default_city_region_id',
            'default_city_area_id',
            'type',
            'latitude',
            'longitude',
        ]));
    }
}
