<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationDatabaseInspector
{
    /**
     * @return array<string, string>
     */
    public function datasetTableKeys(): array
    {
        return [
            'provinces' => 'province',
            'cities' => 'city',
            'city_regions' => 'city_region',
            'city_areas' => 'city_area',
            'neighborhoods' => 'neighborhood',
            'aliases' => 'location_alias',
        ];
    }

    public function tableExistsForKey(string $key): bool
    {
        return Schema::hasTable(LocationModelResolver::table($key));
    }

    /**
     * @param  array<int, string>|null  $datasets
     * @return array<int, string>
     */
    public function missingDatasetTables(?array $datasets = null, bool $includeDataVersion = false): array
    {
        $missing = [];
        $keys = $this->datasetTableKeys();
        $datasets ??= array_keys($keys);

        foreach ($datasets as $dataset) {
            $key = $keys[$dataset] ?? null;

            if ($key !== null && ! $this->tableExistsForKey($key)) {
                $missing[] = LocationModelResolver::table($key);
            }
        }

        if ($includeDataVersion && ! $this->tableExistsForKey('data_version')) {
            $missing[] = LocationModelResolver::table('data_version');
        }

        return array_values(array_unique($missing));
    }

    /**
     * @return array<string, int|null>
     */
    public function datasetCounts(): array
    {
        $counts = [];

        foreach ($this->datasetTableKeys() as $dataset => $key) {
            if (! $this->tableExistsForKey($key)) {
                $counts[$dataset] = null;

                continue;
            }

            $counts[$dataset] = DB::table(LocationModelResolver::table($key))->count();
        }

        return $counts;
    }

    /**
     * @return array<string, int|null>
     */
    public function packageActiveDatasetCounts(): array
    {
        $counts = [];

        foreach ($this->datasetTableKeys() as $dataset => $key) {
            if (! $this->tableExistsForKey($key)) {
                $counts[$dataset] = null;

                continue;
            }

            $table = LocationModelResolver::table($key);
            $query = DB::table($table);

            if (! Schema::hasColumn($table, 'source')) {
                $counts[$dataset] = null;

                continue;
            }

            $query->where('source', 'package');

            if ($dataset !== 'aliases') {
                if (! Schema::hasColumn($table, 'is_active') || ! Schema::hasColumn($table, 'deprecated_at')) {
                    $counts[$dataset] = null;

                    continue;
                }

                $query->where('is_active', true)->whereNull('deprecated_at');
            }

            $counts[$dataset] = $query->count();
        }

        return $counts;
    }

    /**
     * @return array<string, bool>
     */
    public function configuredModelsExist(): array
    {
        $models = [];

        foreach (['province', 'city', 'city_region', 'city_area', 'neighborhood', 'location_alias', 'data_version'] as $key) {
            $class = LocationModelResolver::model($key);
            $models[$key] = class_exists($class) && is_subclass_of($class, Model::class);
        }

        return $models;
    }

    public function latestAppliedVersion(): ?string
    {
        if (! $this->tableExistsForKey('data_version')) {
            return null;
        }

        $version = DB::table(LocationModelResolver::table('data_version'))
            ->whereNotNull('applied_at')
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->value('data_version');

        return is_string($version) && $version !== '' ? $version : null;
    }
}
