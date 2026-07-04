<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use InvalidArgumentException;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Tests\TestCase;

class DataRepositoryTest extends TestCase
{
    public function test_repository_can_read_manifest_and_each_dataset(): void
    {
        $repository = $this->app->make(LocationDataRepository::class);

        self::assertSame('0.2.0-dev', $repository->dataVersion());
        $manifest = $repository->manifest();

        self::assertSame('IR', $manifest['country_code']);
        self::assertCount($manifest['counts']['counties'], $repository->counties());
        self::assertCount($manifest['counts']['official_districts'], $repository->officialDistricts());
        self::assertCount($manifest['counts']['rural_districts'], $repository->ruralDistricts());
        self::assertCount($manifest['counts']['neighborhood_region'], $repository->neighborhoodRegion());

        foreach (LocationDataManifest::datasets() as $dataset) {
            self::assertCount($manifest['counts'][$dataset], $repository->all($dataset));
        }
    }

    public function test_data_count_returns_dataset_counts(): void
    {
        $repository = $this->app->make(LocationDataRepository::class);

        foreach (LocationDataManifest::datasets() as $dataset) {
            self::assertSame(count($repository->all($dataset)), $repository->count($dataset));
        }
    }

    public function test_unknown_dataset_throws_clear_exception(): void
    {
        $repository = $this->app->make(LocationDataRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Iran Locations dataset [districts].');

        $repository->all('districts');
    }
}
