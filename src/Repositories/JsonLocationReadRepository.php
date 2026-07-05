<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Support\LocationModelResolver;
use Zarbin\IranLocations\Support\LocationRecord;

class JsonLocationReadRepository implements LocationReadRepository
{
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $recordMaps = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $datasetCache = [];

    public function __construct(
        private readonly LocationDataRepository $data,
        private readonly LocationNormalizer $normalizer,
    ) {}

    public function all(string $type, array $filters = []): Collection
    {
        $locationType = LocationModelResolver::normalizeLocationType($type);
        $dataset = LocationModelResolver::datasetForLocationType($locationType);
        $filters = $this->filtersWithDefaultStatus($this->normalizeFilterAliases($this->filledFilters($filters)));

        if ($this->hasDatabaseIdFilters($filters)) {
            return collect();
        }

        return collect($this->recordsForDataset($dataset))
            ->filter(fn (array $record): bool => $this->matches($locationType, $record, $filters))
            ->pipe(fn (Collection $records): Collection => $this->sortRecords($records, $filters))
            ->values()
            ->map(fn (array $record): LocationRecord => $this->record($locationType, $dataset, $record));
    }

    public function find(string $type, string $code): ?LocationRecord
    {
        return $this->all($type, ['code' => $code])->first();
    }

    public function options(string $type, array $filters = [], ?int $limit = null): Collection
    {
        $records = $this->all($type, $filters);

        if ($limit !== null) {
            $records = $records->take($limit);
        }

        return $records->map(fn (LocationRecord $record): array => $record->option())->values();
    }

    public function search(string $term, array $types = [], ?int $limit = null): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return collect();
        }

        $types = $types === [] ? LocationModelResolver::locationTypeKeys() : $types;
        $results = collect();

        foreach ($types as $type) {
            foreach ($this->all($type, ['q' => $term]) as $record) {
                $results->push($record);

                if ($limit !== null && $results->count() >= $limit) {
                    return $results->values();
                }
            }
        }

        return $results->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filledFilters(array $filters): array
    {
        return array_filter(
            $filters,
            fn (mixed $value): bool => ! $this->blank($value),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filtersWithDefaultStatus(array $filters): array
    {
        if (! array_key_exists('status', $filters) || $this->blank($filters['status'])) {
            $filters['status'] = 'active';
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilterAliases(array $filters): array
    {
        if (array_key_exists('city_region_code', $filters) && ! array_key_exists('region_code', $filters)) {
            $filters['region_code'] = $filters['city_region_code'];
        }

        if (array_key_exists('region_code', $filters) && ! array_key_exists('city_region_code', $filters)) {
            $filters['city_region_code'] = $filters['region_code'];
        }

        if (array_key_exists('city_area_code', $filters) && ! array_key_exists('area_code', $filters)) {
            $filters['area_code'] = $filters['city_area_code'];
        }

        if (array_key_exists('area_code', $filters) && ! array_key_exists('city_area_code', $filters)) {
            $filters['city_area_code'] = $filters['area_code'];
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function hasDatabaseIdFilters(array $filters): bool
    {
        foreach ($this->databaseIdFilterFields() as $field) {
            if (! $this->blank($filters[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function databaseIdFilterFields(): array
    {
        return [
            'province_id',
            'county_id',
            'official_district_id',
            'rural_district_id',
            'city_id',
            'region_id',
            'city_region_id',
            'area_id',
            'city_area_id',
            'neighborhood_id',
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $filters
     */
    private function matches(string $type, array $record, array $filters): bool
    {
        if (! $this->matchesStatus($record, $this->string($filters['status'] ?? 'active'))) {
            return false;
        }

        foreach (['source', 'code', 'slug', 'type', 'number'] as $field) {
            if (! array_key_exists($field, $filters)) {
                continue;
            }

            $expected = $this->string($filters[$field]);

            if ($expected === null || $expected === 'all') {
                continue;
            }

            if ((string) ($record[$field] ?? '') !== $expected) {
                return false;
            }
        }

        if (($capital = $this->boolean($filters['is_capital'] ?? null)) !== null
            && (bool) ($record['is_province_capital'] ?? false) !== $capital) {
            return false;
        }

        if (($query = $this->string($filters['q'] ?? null)) !== null && ! $this->matchesSearch($record, $query)) {
            return false;
        }

        foreach ($this->codeFilterFields() as $field) {
            $expected = $this->string($filters[$field] ?? null);

            if ($expected !== null && ! $this->matchesCodeFilter($type, $record, $field, $expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesStatus(array $record, ?string $status): bool
    {
        return match ($status) {
            'all' => true,
            'inactive' => (bool) ($record['is_active'] ?? true) === false,
            'deprecated' => ! $this->blank($record['deprecated_at'] ?? null),
            default => (bool) ($record['is_active'] ?? true) === true
                && $this->blank($record['deprecated_at'] ?? null),
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesSearch(array $record, string $term): bool
    {
        $rawNeedle = Str::lower($term);
        $normalizedNeedle = Str::lower($this->normalizer->search($term));

        foreach (['code', 'name_fa', 'display_name_fa', 'name_en', 'normalized_name', 'slug'] as $field) {
            $value = $record[$field] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $haystack = Str::lower($value);
            $normalizedHaystack = Str::lower($this->normalizer->search($value));

            if (str_contains($haystack, $rawNeedle)
                || ($normalizedNeedle !== '' && str_contains($normalizedHaystack, $normalizedNeedle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function codeFilterFields(): array
    {
        return [
            'province_code',
            'county_code',
            'official_district_code',
            'rural_district_code',
            'city_code',
            'city_region_code',
            'region_code',
            'city_area_code',
            'area_code',
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesCodeFilter(string $type, array $record, string $field, string $expected): bool
    {
        if (array_key_exists($field, $record) && ! $this->blank($record[$field])) {
            return (string) $record[$field] === $expected;
        }

        return match ($field) {
            'province_code', 'county_code', 'official_district_code' => $this->matchesParentCode($type, $record, $field, $expected),
            'rural_district_code' => $type === 'rural_district' && (string) ($record['code'] ?? '') === $expected,
            'city_code' => $this->matchesCityCode($type, $record, $expected),
            'city_region_code', 'region_code' => $this->matchesRegionCode($type, $record, $expected),
            'city_area_code', 'area_code' => $this->matchesAreaCode($type, $record, $expected),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesParentCode(string $type, array $record, string $field, string $expected): bool
    {
        if (array_key_exists($field, $record)) {
            return (string) ($record[$field] ?? '') === $expected;
        }

        $city = $this->relatedCity($type, $record);

        return $city !== null && (string) ($city[$field] ?? '') === $expected;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesCityCode(string $type, array $record, string $expected): bool
    {
        if ($type === 'city') {
            return (string) ($record['code'] ?? '') === $expected;
        }

        if (array_key_exists('city_code', $record)) {
            return (string) ($record['city_code'] ?? '') === $expected;
        }

        $city = $this->relatedCity($type, $record);

        return $city !== null && (string) ($city['code'] ?? '') === $expected;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesRegionCode(string $type, array $record, string $expected): bool
    {
        if ($type === 'city_region') {
            return (string) ($record['code'] ?? '') === $expected;
        }

        if ((string) ($record['city_region_code'] ?? '') === $expected
            || (string) ($record['default_city_region_code'] ?? '') === $expected) {
            return true;
        }

        return $type === 'neighborhood'
            && $this->neighborhoodRegionIncludes((string) ($record['code'] ?? ''), $expected);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchesAreaCode(string $type, array $record, string $expected): bool
    {
        if ($type === 'city_area') {
            return (string) ($record['code'] ?? '') === $expected;
        }

        return (string) ($record['city_area_code'] ?? '') === $expected
            || (string) ($record['default_city_area_code'] ?? '') === $expected;
    }

    private function neighborhoodRegionIncludes(string $neighborhoodCode, string $regionCode): bool
    {
        if ($neighborhoodCode === '') {
            return false;
        }

        foreach ($this->recordsForDataset('neighborhood_region') as $record) {
            if ((string) ($record['neighborhood_code'] ?? '') !== $neighborhoodCode
                || (string) ($record['city_region_code'] ?? '') !== $regionCode) {
                continue;
            }

            return $this->matchesStatus($record, 'active');
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function relatedCity(string $type, array $record): ?array
    {
        if ($type === 'city') {
            return $record;
        }

        $cityCode = $this->string($record['city_code'] ?? null);

        if ($cityCode !== null) {
            return $this->recordByCode('cities', $cityCode);
        }

        $regionCode = $this->string($record['city_region_code'] ?? $record['default_city_region_code'] ?? null);
        $region = $regionCode === null ? null : $this->recordByCode('city_regions', $regionCode);
        $regionCityCode = $this->string($region['city_code'] ?? null);

        return $regionCityCode === null ? null : $this->recordByCode('cities', $regionCityCode);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function record(string $type, string $dataset, array $record): LocationRecord
    {
        return new LocationRecord(array_merge($record, [
            'location_type' => $type,
            'dataset' => $dataset,
        ]));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRecords(Collection $records, array $filters): Collection
    {
        $sort = $this->string($filters['sort'] ?? null);
        $descending = is_string($sort) && str_starts_with($sort, '-');
        $key = ltrim((string) $sort, '-');
        $field = match ($key) {
            'code' => 'code',
            'number' => 'number',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            default => 'normalized_name',
        };

        return $records->sortBy(
            fn (array $record): mixed => $record[$field] ?? $record['name_fa'] ?? $record['code'] ?? '',
            SORT_REGULAR,
            $descending,
        )->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recordsForDataset(string $dataset): array
    {
        if (isset($this->datasetCache[$dataset])) {
            return $this->datasetCache[$dataset];
        }

        if (! (bool) config('iran-locations.storage.json.cache', false)) {
            return $this->datasetCache[$dataset] = $this->data->all($dataset);
        }

        $records = Cache::rememberForever(
            $this->cacheKey($dataset),
            fn (): array => $this->data->all($dataset),
        );

        return $this->datasetCache[$dataset] = $records;
    }

    private function cacheKey(string $dataset): string
    {
        $base = config('iran-locations.storage.json.cache_key', 'iran_locations.json_data');
        $base = is_string($base) && $base !== '' ? $base : 'iran_locations.json_data';
        $checksum = $this->data->manifest()['checksum'] ?? null;
        $checksum = is_string($checksum) && $checksum !== '' ? $checksum : 'no-checksum';

        return $base.'.'.$this->data->dataVersion().'.'.$checksum.'.'.$dataset;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function recordsByCode(string $dataset): array
    {
        if (isset($this->recordMaps[$dataset])) {
            return $this->recordMaps[$dataset];
        }

        $records = [];

        foreach ($this->recordsForDataset($dataset) as $record) {
            $code = $this->string($record['code'] ?? null);

            if ($code !== null) {
                $records[$code] = $record;
            }
        }

        return $this->recordMaps[$dataset] = $records;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recordByCode(string $dataset, string $code): ?array
    {
        return $this->recordsByCode($dataset)[$code] ?? null;
    }

    private function string(mixed $value): ?string
    {
        if ($this->blank($value)) {
            return null;
        }

        return is_string($value) || is_numeric($value)
            ? trim((string) $value)
            : null;
    }

    private function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
