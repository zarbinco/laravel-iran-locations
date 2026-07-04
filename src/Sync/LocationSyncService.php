<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Sync;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\LocationDataManifest;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationSyncService
{
    /**
     * @var array<string, array<string, int>>
     */
    private array $identityMap = [];

    private int $virtualId = -1;

    public function __construct(
        private readonly LocationDataRepository $repository,
        private readonly LocationDataValidator $validator,
        private readonly LocationNormalizer $normalizer,
        private readonly LocationDatabaseInspector $database,
    ) {}

    public function sync(?LocationSyncOptions $options = null): LocationSyncResult
    {
        $options ??= LocationSyncOptions::make();
        $this->identityMap = [];
        $this->virtualId = -1;

        $validation = $this->validator->validate();

        if (! $validation['ok']) {
            throw LocationSyncException::validationFailed($validation['errors']);
        }

        if ($options->deprecateMissing && $this->deleteBehavior() === 'delete') {
            throw new LocationSyncException('Hard delete behavior is not supported by the safe Iran Locations sync engine.');
        }

        $datasets = $this->datasetsFor($options);
        $missingTables = $this->database->missingDatasetTables($datasets, includeDataVersion: ! $options->dryRun);

        if ($missingTables !== []) {
            throw new LocationSyncException('Iran Locations database tables are missing: '.implode(', ', $missingTables).'. Run migrations first.');
        }

        $manifest = $this->repository->manifest();
        $dataVersion = $this->repository->dataVersion();

        if ($options->dryRun) {
            return $this->run($datasets, $options, $dataVersion, $manifest);
        }

        return DB::transaction(function () use ($datasets, $options, $dataVersion, $manifest): LocationSyncResult {
            $result = $this->run($datasets, $options, $dataVersion, $manifest);

            if ($result->isSuccessful()) {
                $this->recordDataVersion($result, $manifest);
            }

            return $result;
        });
    }

    /**
     * @param  array<int, string>  $datasets
     * @param  array<string, mixed>  $manifest
     */
    private function run(array $datasets, LocationSyncOptions $options, string $dataVersion, array $manifest): LocationSyncResult
    {
        $results = [];

        foreach ($datasets as $dataset) {
            $results[] = match ($dataset) {
                'aliases' => $this->syncAliases($options),
                'neighborhood_region' => $this->syncNeighborhoodRegion($options),
                default => $this->syncModelDataset($dataset, $options, $dataVersion),
            };
        }

        return new LocationSyncResult($dataVersion, $options->dryRun, $results);
    }

    private function syncModelDataset(string $dataset, LocationSyncOptions $options, string $dataVersion): LocationSyncDatasetResult
    {
        $result = new LocationSyncDatasetResult($dataset);
        $records = $this->repository->all($dataset);
        $incomingCodes = [];

        foreach ($records as $index => $record) {
            $code = $this->recordCode($record, $index);

            if ($code === null) {
                $result->add(new LocationSyncChange($dataset, "record-{$index}", 'fail', message: 'Record is missing a stable code.'));

                continue;
            }

            $incomingCodes[$code] = true;

            $payload = $this->payloadFor($dataset, $record, $dataVersion, $message);

            if ($payload === null) {
                $result->add(new LocationSyncChange($dataset, $code, 'fail', after: $record, message: $message));

                continue;
            }

            $this->syncModelRecord($dataset, $code, $payload, $options, $result);
        }

        $this->handleMissingPackageRecords($dataset, $incomingCodes, $records, $options, $dataVersion, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncModelRecord(
        string $dataset,
        string $code,
        array $payload,
        LocationSyncOptions $options,
        LocationSyncDatasetResult $result,
    ): void {
        $existing = $this->query($dataset)->where('code', $code)->first();

        if (! $existing instanceof Model) {
            $result->add(new LocationSyncChange($dataset, $code, 'create', after: $payload));

            if (! $options->dryRun) {
                $model = $this->newModel($dataset);
                $model->fill($payload);
                $model->saveQuietly();
                $this->remember($dataset, $code, (int) $model->getKey());
            } else {
                $this->remember($dataset, $code, $this->nextVirtualId());
            }

            return;
        }

        $this->remember($dataset, $code, (int) $existing->getKey());

        $source = $existing->getAttribute('source');

        if ($source === 'custom') {
            $result->add(new LocationSyncChange($dataset, $code, 'skip', before: $this->snapshot($existing, $payload), after: $payload, message: 'Custom record was preserved.'));

            return;
        }

        if ($source !== 'package' && ! $options->force) {
            $result->add(new LocationSyncChange($dataset, $code, 'skip', before: $this->snapshot($existing, $payload), after: $payload, message: 'Record source is not package-managed.'));

            return;
        }

        $this->preserveDisplayOverride($existing, $payload);

        $changes = $this->changedAttributes($existing, $payload);

        if ($changes === []) {
            $result->add(new LocationSyncChange($dataset, $code, 'unchanged', before: $this->snapshot($existing, $payload), after: $payload));

            return;
        }

        $result->add(new LocationSyncChange($dataset, $code, 'update', before: $this->snapshot($existing, $payload), after: array_merge($this->snapshot($existing, $payload), $changes)));

        if (! $options->dryRun) {
            $existing->fill($changes);
            $existing->saveQuietly();
            $this->remember($dataset, $code, (int) $existing->getKey());
        }
    }

    private function syncAliases(LocationSyncOptions $options): LocationSyncDatasetResult
    {
        $dataset = 'aliases';
        $result = new LocationSyncDatasetResult($dataset);

        foreach ($this->repository->aliases() as $index => $record) {
            $code = $this->aliasCode($record, $index);
            $payload = $this->aliasPayload($record, $message);

            if ($payload === null) {
                $result->add(new LocationSyncChange($dataset, $code, 'fail', after: $record, message: $message));

                continue;
            }

            $existing = $this->query('aliases')
                ->where('location_type', $payload['location_type'])
                ->where('location_id', $payload['location_id'])
                ->where('normalized_alias', $payload['normalized_alias'])
                ->first();

            if (! $existing instanceof Model) {
                $result->add(new LocationSyncChange($dataset, $code, 'create', after: $payload));

                if (! $options->dryRun) {
                    $alias = $this->newModel('aliases');
                    $alias->fill($payload);
                    $alias->saveQuietly();
                }

                continue;
            }

            if ($existing->getAttribute('source') === 'custom') {
                $result->add(new LocationSyncChange($dataset, $code, 'skip', before: $this->snapshot($existing, $payload), after: $payload, message: 'Custom alias was preserved.'));

                continue;
            }

            $changes = $this->changedAttributes($existing, $payload);

            if ($changes === []) {
                $result->add(new LocationSyncChange($dataset, $code, 'unchanged', before: $this->snapshot($existing, $payload), after: $payload));

                continue;
            }

            $result->add(new LocationSyncChange($dataset, $code, 'update', before: $this->snapshot($existing, $payload), after: array_merge($this->snapshot($existing, $payload), $changes)));

            if (! $options->dryRun) {
                $existing->fill($changes);
                $existing->saveQuietly();
            }
        }

        return $result;
    }

    private function syncNeighborhoodRegion(LocationSyncOptions $options): LocationSyncDatasetResult
    {
        $dataset = 'neighborhood_region';
        $result = new LocationSyncDatasetResult($dataset);
        $table = LocationModelResolver::table('neighborhood_region');

        foreach ($this->repository->neighborhoodRegion() as $index => $record) {
            $neighborhoodCode = $this->string($record['neighborhood_code'] ?? null);
            $cityRegionCode = $this->string($record['city_region_code'] ?? null);
            $changeCode = ($neighborhoodCode ?? "record-{$index}").':'.($cityRegionCode ?? 'city-region');
            $neighborhoodId = $this->resolveId('neighborhoods', $neighborhoodCode);
            $cityRegionId = $this->resolveId('city_regions', $cityRegionCode);

            if ($neighborhoodId === null || $cityRegionId === null) {
                $result->add(new LocationSyncChange($dataset, $changeCode, 'fail', after: $record, message: 'Neighborhood-region dependency is missing.'));

                continue;
            }

            $payload = [
                'neighborhood_id' => $neighborhoodId,
                'city_region_id' => $cityRegionId,
                'is_primary' => $this->boolean($record['is_primary'] ?? true),
                'source' => $this->string($record['source'] ?? null) ?? 'package',
                'confidence' => $this->integer($record['confidence'] ?? null),
            ];

            $existing = DB::table($table)
                ->where('neighborhood_id', $neighborhoodId)
                ->where('city_region_id', $cityRegionId)
                ->first();

            if ($existing === null) {
                $result->add(new LocationSyncChange($dataset, $changeCode, 'create', after: $payload));

                if (! $options->dryRun) {
                    DB::table($table)->insert([
                        ...$payload,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            if (($existing->source ?? null) === 'custom') {
                $result->add(new LocationSyncChange($dataset, $changeCode, 'skip', before: (array) $existing, after: $payload, message: 'Custom neighborhood-region mapping was preserved.'));

                continue;
            }

            $changes = [];

            foreach (['is_primary', 'source', 'confidence'] as $key) {
                if (! $this->sameValue($existing->{$key} ?? null, $payload[$key])) {
                    $changes[$key] = $payload[$key];
                }
            }

            if ($changes === []) {
                $result->add(new LocationSyncChange($dataset, $changeCode, 'unchanged', before: (array) $existing, after: $payload));

                continue;
            }

            $result->add(new LocationSyncChange($dataset, $changeCode, 'update', before: (array) $existing, after: array_merge((array) $existing, $changes)));

            if (! $options->dryRun) {
                DB::table($table)
                    ->where('neighborhood_id', $neighborhoodId)
                    ->where('city_region_id', $cityRegionId)
                    ->update([
                        ...$changes,
                        'updated_at' => now(),
                    ]);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, true>  $incomingCodes
     * @param  array<int, array<string, mixed>>  $records
     */
    private function handleMissingPackageRecords(
        string $dataset,
        array $incomingCodes,
        array $records,
        LocationSyncOptions $options,
        string $dataVersion,
        LocationSyncDatasetResult $result,
    ): void {
        if (! $this->shouldDeprecateMissing($records, $options)) {
            return;
        }

        $models = $this->query($dataset)
            ->where('source', 'package')
            ->get();

        foreach ($models as $model) {
            $code = $model->getAttribute('code');

            if (! is_string($code) || isset($incomingCodes[$code])) {
                continue;
            }

            if ($model->getAttribute('deprecated_at') !== null && $model->getAttribute('is_active') === false) {
                $result->add(new LocationSyncChange($dataset, $code, 'unchanged', before: $this->deprecationSnapshot($model), message: 'Missing package record was already deprecated.'));

                continue;
            }

            $after = [
                'is_active' => false,
                'deprecated_at' => $model->freshTimestamp(),
                'data_version' => $dataVersion,
            ];

            $result->add(new LocationSyncChange($dataset, $code, 'deprecate', before: $this->deprecationSnapshot($model), after: $after, message: 'Package record is missing from current package data.'));

            if (! $options->dryRun) {
                $model->forceFill($after);
                $model->saveQuietly();
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    private function shouldDeprecateMissing(array $records, LocationSyncOptions $options): bool
    {
        if (! $options->deprecateMissing || $this->deleteBehavior() !== 'deprecate') {
            return false;
        }

        return $records !== [] || $options->hasExplicitDatasets();
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function payloadFor(string $dataset, array $record, string $dataVersion, ?string &$message): ?array
    {
        $message = null;

        return match ($dataset) {
            'provinces' => $this->provincePayload($record, $dataVersion),
            'counties' => $this->countyPayload($record, $dataVersion, $message),
            'official_districts' => $this->officialDistrictPayload($record, $dataVersion, $message),
            'rural_districts' => $this->ruralDistrictPayload($record, $dataVersion, $message),
            'cities' => $this->cityPayload($record, $dataVersion, $message),
            'city_regions' => $this->cityRegionPayload($record, $dataVersion, $message),
            'city_areas' => $this->cityAreaPayload($record, $dataVersion, $message),
            'neighborhoods' => $this->neighborhoodPayload($record, $dataVersion, $message),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function provincePayload(array $record, string $dataVersion): array
    {
        return $this->withLifecycle([
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function countyPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $provinceCode = $this->string($record['province_code'] ?? null);
        $provinceId = $this->resolveId('provinces', $provinceCode);

        if ($provinceId === null) {
            $message = "Missing province dependency [{$provinceCode}].";

            return null;
        }

        return $this->withLifecycle([
            'province_id' => $provinceId,
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function officialDistrictPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $provinceCode = $this->string($record['province_code'] ?? null);
        $countyCode = $this->string($record['county_code'] ?? null);
        $provinceId = $this->resolveId('provinces', $provinceCode);
        $countyId = $this->resolveId('counties', $countyCode);

        if ($provinceId === null) {
            $message = "Missing province dependency [{$provinceCode}].";

            return null;
        }

        if ($countyId === null) {
            $message = "Missing county dependency [{$countyCode}].";

            return null;
        }

        return $this->withLifecycle([
            'province_id' => $provinceId,
            'county_id' => $countyId,
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function ruralDistrictPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $provinceCode = $this->string($record['province_code'] ?? null);
        $countyCode = $this->string($record['county_code'] ?? null);
        $districtCode = $this->string($record['official_district_code'] ?? null);
        $provinceId = $this->resolveId('provinces', $provinceCode);
        $countyId = $this->resolveId('counties', $countyCode);
        $districtId = $this->resolveId('official_districts', $districtCode);

        if ($provinceId === null) {
            $message = "Missing province dependency [{$provinceCode}].";

            return null;
        }

        if ($countyId === null) {
            $message = "Missing county dependency [{$countyCode}].";

            return null;
        }

        if ($districtId === null) {
            $message = "Missing official district dependency [{$districtCode}].";

            return null;
        }

        return $this->withLifecycle([
            'province_id' => $provinceId,
            'county_id' => $countyId,
            'official_district_id' => $districtId,
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function cityPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $provinceCode = $this->string($record['province_code'] ?? null);
        $countyCode = $this->string($record['county_code'] ?? null);
        $districtCode = $this->string($record['official_district_code'] ?? null);
        $provinceId = $this->resolveId('provinces', $provinceCode);
        $countyId = $countyCode === null ? null : $this->resolveId('counties', $countyCode);
        $districtId = $districtCode === null ? null : $this->resolveId('official_districts', $districtCode);

        if ($provinceId === null) {
            $message = "Missing province dependency [{$provinceCode}].";

            return null;
        }

        if ($countyCode !== null && $countyId === null) {
            $message = "Missing county dependency [{$countyCode}].";

            return null;
        }

        if ($districtCode !== null && $districtId === null) {
            $message = "Missing official district dependency [{$districtCode}].";

            return null;
        }

        return $this->withLifecycle([
            'province_id' => $provinceId,
            'county_id' => $countyId,
            'official_district_id' => $districtId,
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
            'is_province_capital' => $this->boolean($record['is_province_capital'] ?? false),
            'latitude' => $this->decimal($record['latitude'] ?? null),
            'longitude' => $this->decimal($record['longitude'] ?? null),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function cityRegionPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $cityCode = $this->string($record['city_code'] ?? null);
        $cityId = $this->resolveId('cities', $cityCode);

        if ($cityId === null) {
            $message = "Missing city dependency [{$cityCode}].";

            return null;
        }

        return $this->withLifecycle([
            'city_id' => $cityId,
            'code' => $this->string($record['code'] ?? null),
            'number' => $this->integer($record['number'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
            'type' => $this->string($record['type'] ?? null) ?? 'municipal_region',
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function cityAreaPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $regionCode = $this->firstString($record, ['city_region_code', 'region_code']);
        $regionId = $this->resolveId('city_regions', $regionCode);

        if ($regionId === null) {
            $message = "Missing city region dependency [{$regionCode}].";

            return null;
        }

        return $this->withLifecycle([
            'city_region_id' => $regionId,
            'code' => $this->string($record['code'] ?? null),
            'number' => $this->integer($record['number'] ?? null),
            ...$this->namePayload($record, includeEnglishName: false),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function neighborhoodPayload(array $record, string $dataVersion, ?string &$message): ?array
    {
        $cityCode = $this->string($record['city_code'] ?? null);
        $cityId = $this->resolveId('cities', $cityCode);

        if ($cityId === null) {
            $message = "Missing city dependency [{$cityCode}].";

            return null;
        }

        $regionCode = $this->firstString($record, ['default_city_region_code', 'city_region_code', 'region_code']);
        $areaCode = $this->firstString($record, ['default_city_area_code', 'city_area_code', 'area_code']);

        return $this->withLifecycle([
            'city_id' => $cityId,
            'default_city_region_id' => $regionCode === null ? null : $this->resolveId('city_regions', $regionCode),
            'default_city_area_id' => $areaCode === null ? null : $this->resolveId('city_areas', $areaCode),
            'code' => $this->string($record['code'] ?? null),
            ...$this->namePayload($record, includeEnglishName: true),
            'type' => $this->string($record['type'] ?? null) ?? 'neighborhood',
            'latitude' => $this->decimal($record['latitude'] ?? null),
            'longitude' => $this->decimal($record['longitude'] ?? null),
        ], $record, $dataVersion);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function aliasPayload(array $record, ?string &$message): ?array
    {
        $message = null;
        $locationType = $this->string($record['location_type'] ?? null);
        $locationCode = $this->string($record['location_code'] ?? null);
        $dataset = $this->aliasDataset($locationType);

        if ($dataset === null || $locationCode === null) {
            $message = 'Alias target type or code is missing.';

            return null;
        }

        $locationId = $this->resolveId($dataset, $locationCode);

        if ($locationId === null) {
            $message = "Missing alias target [{$locationType}:{$locationCode}].";

            return null;
        }

        $alias = $this->string($record['alias'] ?? null);

        if ($alias === null) {
            $message = 'Alias value is missing.';

            return null;
        }

        return [
            'location_type' => $this->modelClass($dataset),
            'location_id' => $locationId,
            'alias' => $alias,
            'normalized_alias' => $this->string($record['normalized_alias'] ?? null) ?? $this->normalizer->search($alias),
            'reason' => $this->string($record['reason'] ?? null),
            'source' => 'package',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function withLifecycle(array $payload, array $record, string $dataVersion): array
    {
        return [
            ...$payload,
            'is_active' => $this->boolean($record['is_active'] ?? true),
            'source' => 'package',
            'source_version' => $this->string($record['source_version'] ?? null) ?? $this->manifestSourceVersion(),
            'data_version' => $dataVersion,
            'deprecated_at' => null,
            'replaced_by_id' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function namePayload(array $record, bool $includeEnglishName): array
    {
        $name = $this->string($record['name_fa'] ?? null) ?? '';
        $payload = [
            'name_fa' => $name,
            'slug' => $this->string($record['slug'] ?? null) ?? $this->normalizer->slug($name),
            'normalized_name' => $this->string($record['normalized_name'] ?? null) ?? $this->normalizer->search($name),
            'display_name_fa' => $this->string($record['display_name_fa'] ?? null),
        ];

        if ($includeEnglishName) {
            $payload['name_en'] = $this->string($record['name_en'] ?? null);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function changedAttributes(Model $model, array $payload): array
    {
        $changes = [];

        foreach ($payload as $key => $value) {
            if (! $this->sameValue($model->getAttribute($key), $value)) {
                $changes[$key] = $value;
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function preserveDisplayOverride(Model $model, array &$payload): void
    {
        $display = $this->string($model->getAttribute('display_name_fa'));

        if ($display !== null) {
            unset($payload['display_name_fa']);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function snapshot(Model $model, array $payload): array
    {
        $snapshot = [];

        foreach (array_keys($payload) as $key) {
            $snapshot[$key] = $model->getAttribute($key);
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    private function deprecationSnapshot(Model $model): array
    {
        return [
            'code' => $model->getAttribute('code'),
            'is_active' => $model->getAttribute('is_active'),
            'deprecated_at' => $model->getAttribute('deprecated_at'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }

    private function sameValue(mixed $current, mixed $incoming): bool
    {
        if ($current === null || $incoming === null) {
            return $current === $incoming;
        }

        if (is_bool($current) || is_bool($incoming)) {
            return (bool) $current === (bool) $incoming;
        }

        if (is_numeric($current) && is_numeric($incoming)) {
            return (float) $current === (float) $incoming;
        }

        return (string) $current === (string) $incoming;
    }

    private function resolveId(string $dataset, ?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        if (isset($this->identityMap[$dataset][$code])) {
            return $this->identityMap[$dataset][$code];
        }

        $id = $this->query($dataset)->where('code', $code)->value((new ($this->modelClass($dataset)))->getKeyName());

        if (! is_numeric($id)) {
            return null;
        }

        $this->identityMap[$dataset][$code] = (int) $id;

        return (int) $id;
    }

    private function remember(string $dataset, string $code, int $id): void
    {
        $this->identityMap[$dataset][$code] = $id;
    }

    private function nextVirtualId(): int
    {
        return $this->virtualId--;
    }

    private function query(string $dataset): Builder
    {
        return $this->newModel($dataset)->newQuery();
    }

    private function newModel(string $dataset): Model
    {
        $class = $this->modelClass($dataset);

        return new $class;
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(string $dataset): string
    {
        $key = match ($dataset) {
            'provinces' => 'province',
            'counties' => 'county',
            'official_districts' => 'official_district',
            'rural_districts' => 'rural_district',
            'cities' => 'city',
            'city_regions' => 'city_region',
            'city_areas' => 'city_area',
            'neighborhoods' => 'neighborhood',
            'aliases' => 'location_alias',
            'data_versions' => 'data_version',
            default => throw new LocationSyncException("Unknown sync dataset [{$dataset}]."),
        };

        /** @var class-string<Model> $class */
        $class = LocationModelResolver::model($key);

        return $class;
    }

    private function aliasDataset(?string $type): ?string
    {
        return match ($type) {
            'province', 'provinces' => 'provinces',
            'city', 'cities' => 'cities',
            'city_region', 'city_regions' => 'city_regions',
            'city_area', 'city_areas' => 'city_areas',
            'neighborhood', 'neighborhoods' => 'neighborhoods',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function datasetsFor(LocationSyncOptions $options): array
    {
        $order = LocationDataManifest::datasets();

        if ($options->datasets === null) {
            return $order;
        }

        $requested = array_fill_keys($options->datasets, true);
        $unknown = array_diff(array_keys($requested), $order);

        if ($unknown !== []) {
            throw new LocationSyncException('Unknown sync dataset(s): '.implode(', ', $unknown).'.');
        }

        return array_values(array_filter(
            $order,
            static fn (string $dataset): bool => isset($requested[$dataset]),
        ));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function recordCode(array $record, int $index): ?string
    {
        return $this->string($record['code'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function aliasCode(array $record, int $index): string
    {
        $type = $this->string($record['location_type'] ?? null) ?? 'unknown';
        $target = $this->string($record['location_code'] ?? null) ?? "record-{$index}";
        $alias = $this->string($record['normalized_alias'] ?? null) ?? $this->string($record['alias'] ?? null) ?? 'alias';

        return "{$type}:{$target}:{$alias}";
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    private function firstString(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->string($record[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function decimal(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function deleteBehavior(): string
    {
        return (string) config('iran-locations.data.package_record_delete_behavior', 'deprecate');
    }

    private function manifestSourceVersion(): ?string
    {
        $source = $this->repository->manifest()['source'] ?? null;

        if (! is_array($source)) {
            return null;
        }

        return $this->string($source['version'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function recordDataVersion(LocationSyncResult $result, array $manifest): void
    {
        $class = $this->modelClass('data_versions');
        $source = $manifest['source'] ?? null;
        $packageVersion = is_array($source) ? $this->string($source['version'] ?? null) : null;
        $checksum = $this->string($manifest['checksum'] ?? null);

        $model = new $class([
            'data_version' => $result->dataVersion,
            'package_version' => $packageVersion,
            'checksum' => $checksum,
            'summary' => $result->summary(),
            'applied_at' => now(),
        ]);
        $model->saveQuietly();
    }
}
