<?php

declare(strict_types=1);

namespace Zarbin\IranLocations;

use InvalidArgumentException;
use Zarbin\IranLocations\Contracts\LocationNormalizer;

class IranLocationsManager
{
    public function __construct(
        private readonly LocationNormalizer $normalizer,
    ) {}

    public function normalizer(): LocationNormalizer
    {
        return $this->normalizer;
    }

    public function normalizeForSearch(string $value): string
    {
        return $this->normalizer->search($value);
    }

    public function normalizeForDisplay(string $value): string
    {
        return $this->normalizer->display($value);
    }

    public function table(string $key): string
    {
        $table = config("iran-locations.tables.{$key}");

        if (! is_string($table) || $table === '') {
            throw new InvalidArgumentException("Unknown Iran Locations table key [{$key}].");
        }

        return $table;
    }

    public function model(string $key): string
    {
        $model = config("iran-locations.models.{$key}");

        if (! is_string($model) || $model === '') {
            throw new InvalidArgumentException("Unknown Iran Locations model key [{$key}].");
        }

        return $model;
    }

    public function dataVersion(): string
    {
        return (string) config('iran-locations.data.current_version', '0.1.0-dev');
    }
}
