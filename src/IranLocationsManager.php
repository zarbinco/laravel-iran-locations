<?php

declare(strict_types=1);

namespace Zarbin\IranLocations;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Support\LocationModelResolver;
use Zarbin\IranLocations\Support\LocationRecord;

class IranLocationsManager
{
    public function __construct(
        private readonly LocationNormalizer $normalizer,
        private readonly LocationDataRepository $dataRepository,
        private readonly LocationReadRepository $readRepository,
    ) {}

    public function normalizer(): LocationNormalizer
    {
        return $this->normalizer;
    }

    public function dataRepository(): LocationDataRepository
    {
        return $this->dataRepository;
    }

    public function readRepository(): LocationReadRepository
    {
        return $this->readRepository;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LocationRecord>
     */
    public function all(string $type, array $filters = []): Collection
    {
        return $this->readRepository->all($type, $filters);
    }

    public function find(string $type, string $code): ?LocationRecord
    {
        return $this->readRepository->find($type, $code);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{value: string, label: string, code: string, name_fa: mixed}>
     */
    public function options(string $type, array $filters = [], ?int $limit = null): Collection
    {
        return $this->readRepository->options($type, $filters, $limit);
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, LocationRecord>
     */
    public function search(string $term, array $types = [], ?int $limit = null): Collection
    {
        return $this->readRepository->search($term, $types, $limit);
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
