<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Facades;

use Illuminate\Support\Facades\Facade;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Support\LocationRecord;

/**
 * @method static LocationNormalizer normalizer()
 * @method static LocationDataRepository dataRepository()
 * @method static LocationReadRepository readRepository()
 * @method static \Illuminate\Support\Collection<int, LocationRecord> all(string $type, array $filters = [])
 * @method static LocationRecord|null find(string $type, string $code)
 * @method static \Illuminate\Support\Collection<int, array{value: string, label: string, code: string, name_fa: mixed}> options(string $type, array $filters = [], ?int $limit = null)
 * @method static \Illuminate\Support\Collection<int, LocationRecord> search(string $term, array $types = [], ?int $limit = null)
 * @method static array<string, mixed> dataManifest()
 * @method static int dataCount(string $dataset)
 * @method static string normalizeForSearch(string $value)
 * @method static string normalizeForDisplay(string $value)
 * @method static string table(string $key)
 * @method static string model(string $key)
 * @method static string dataVersion()
 */
class IranLocations extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'iran-locations';
    }
}
