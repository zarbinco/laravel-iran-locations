<?php

declare(strict_types=1);

namespace Zarbin\IranLocations;

use InvalidArgumentException;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Support\LocationModelResolver;

class IranLocationsManager
{
    public function __construct(
        private readonly LocationNormalizer $normalizer,
        private readonly LocationDataRepository $dataRepository,
    ) {}

    public function normalizer(): LocationNormalizer
    {
        return $this->normalizer;
    }

    public function dataRepository(): LocationDataRepository
    {
        return $this->dataRepository;
    }

    /**
     * @return array<string, mixed>
     */
    public function dataManifest(): array
    {
        return $this->dataRepository->manifest();
    }

    public function dataCount(string $dataset): int
    {
        return $this->dataRepository->count($dataset);
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
        try {
            return LocationModelResolver::table($key);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("Unknown Iran Locations table key [{$key}].");
        }
    }

    public function model(string $key): string
    {
        try {
            return LocationModelResolver::model($key);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("Unknown Iran Locations model key [{$key}].");
        }
    }

    public function dataVersion(): string
    {
        return $this->dataRepository->dataVersion();
    }
}
