<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\IranLocationsManager;
use Zarbin\IranLocations\IranLocationsServiceProvider;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Support\PersianCoreLocationNormalizer;
use Zarbin\IranLocations\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_service_provider_loads(): void
    {
        self::assertArrayHasKey(
            IranLocationsServiceProvider::class,
            $this->app->getLoadedProviders(),
        );
    }

    public function test_config_is_merged(): void
    {
        self::assertSame('iran_provinces', config('iran-locations.tables.provinces'));
        self::assertSame('persian-core', config('iran-locations.normalization.driver'));
        self::assertFalse(config('iran-locations.admin.enabled'));
        self::assertFalse(config('iran-locations.api.enabled'));
    }

    public function test_manager_resolves_from_container_and_alias(): void
    {
        self::assertInstanceOf(IranLocationsManager::class, $this->app->make(IranLocationsManager::class));
        self::assertSame($this->app->make(IranLocationsManager::class), $this->app->make('iran-locations'));
    }

    public function test_normalizer_contract_resolves_to_persian_core_adapter(): void
    {
        $normalizer = $this->app->make(LocationNormalizer::class);

        self::assertInstanceOf(LocationNormalizer::class, $normalizer);
        self::assertInstanceOf(PersianCoreLocationNormalizer::class, $normalizer);
    }

    public function test_manager_table_and_model_helpers_work(): void
    {
        $manager = $this->app->make(IranLocationsManager::class);

        self::assertSame('iran_cities', $manager->table('cities'));
        self::assertSame(Province::class, $manager->model('province'));
        self::assertSame('0.1.0-dev', $manager->dataVersion());
    }

    public function test_commands_are_registered(): void
    {
        $commands = Artisan::all();

        foreach ([
            'iran-locations:install',
            'iran-locations:status',
            'iran-locations:sync',
            'iran-locations:doctor',
            'iran-locations:normalize',
        ] as $command) {
            self::assertArrayHasKey($command, $commands);
        }
    }
}
