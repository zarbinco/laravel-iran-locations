<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Facades;

use Illuminate\Support\Facades\Facade;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;

/**
 * @method static LocationNormalizer normalizer()
 * @method static LocationDataRepository dataRepository()
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
