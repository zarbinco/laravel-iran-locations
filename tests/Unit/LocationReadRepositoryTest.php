<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use InvalidArgumentException;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Repositories\DatabaseLocationReadRepository;
use Zarbin\IranLocations\Repositories\JsonLocationReadRepository;
use Zarbin\IranLocations\Tests\TestCase;

class LocationReadRepositoryTest extends TestCase
{
    public function test_default_driver_is_database(): void
    {
        self::assertSame('database', config('iran-locations.storage.driver'));
        self::assertInstanceOf(DatabaseLocationReadRepository::class, $this->app->make(LocationReadRepository::class));
    }

    public function test_json_driver_binds_json_read_repository(): void
    {
        config()->set('iran-locations.storage.driver', 'json');
        $this->app->forgetInstance(LocationReadRepository::class);

        self::assertInstanceOf(JsonLocationReadRepository::class, $this->app->make(LocationReadRepository::class));
    }

    public function test_invalid_driver_fails_clearly(): void
    {
        config()->set('iran-locations.storage.driver', 'redis');
        $this->app->forgetInstance(LocationReadRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported Iran Locations storage driver [redis]. Supported drivers: database, json.');

        $this->app->make(LocationReadRepository::class);
    }
}
