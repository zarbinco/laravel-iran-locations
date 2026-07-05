<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Zarbin\IranLocations\Builders\LocationBuilder;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Support\LocationModelResolver;
use Zarbin\IranLocations\Support\LocationRecord;

class DatabaseLocationReadRepository implements LocationReadRepository
{
    public function __construct(
        private readonly LocationNormalizer $normalizer,
    ) {}

    public function all(string $type, array $filters = []): Collection
    {
        $locationType = LocationModelResolver::normalizeLocationType($type);
        $query = $this->query($locationType);
        $this->applyFilters($query, $filters);

        return $query->get()
            ->map(fn (Model $model): LocationRecord => $this->recordFromModel($locationType, $model))
            ->values();
    }

    public function find(string $type, string $code): ?LocationRecord
    {
        $locationType = LocationModelResolver::normalizeLocationType($type);
        $query = $this->query($locationType);
        $model = $query->getModel();

        if ($query instanceof LocationBuilder) {
            $query->active()->byCode($code);
        } else {
            $this->applyFallbackStatus($query, $model, 'active');
            $query->where($model->qualifyColumn('code'), $code);
        }

        $record = $query->first();

        return $record instanceof Model ? $this->recordFromModel($locationType, $record) : null;
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

    private function query(string $type): Builder
    {
        /** @var class-string<Model> $class */
        $class = LocationModelResolver::modelForLocationType($type);

        return (new $class)->newQuery();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $filters = $this->filtersWithDefaultStatus($this->normalizeFilterAliases($this->filledFilters($filters)));

        if ($query instanceof LocationBuilder) {
            $query->filter($filters);

            if (! array_key_exists('sort', $filters)) {
                $query->ordered();
            }

            return;
        }

        $model = $query->getModel();
        $this->applyFallbackStatus($query, $model, (string) ($filters['status'] ?? 'active'));
        $this->applyFallbackCommonFilters($query, $model, $filters);
        $this->applyFallbackSort($query, $model, $filters['sort'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filledFilters(array $filters): array
    {
        return array_filter(
            $filters,
            static fn (mixed $value): bool => ! blank($value),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filtersWithDefaultStatus(array $filters): array
    {
        if (! array_key_exists('status', $filters) || blank($filters['status'])) {
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

        if (array_key_exists('city_area_code', $filters) && ! array_key_exists('area_code', $filters)) {
            $filters['area_code'] = $filters['city_area_code'];
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFallbackCommonFilters(Builder $query, Model $model, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $this->applyFallbackSearch($query, $model, (string) $filters['q']);
        }

        foreach (['source', 'code', 'slug'] as $field) {
            if (filled($filters[$field] ?? null) && $this->hasColumn($model, $field)) {
                $query->where($model->qualifyColumn($field), $filters[$field]);
            }
        }
    }

    private function applyFallbackSearch(Builder $query, Model $model, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $normalized = trim($this->normalizer->search($term));
        $terms = array_values(array_unique(array_filter(
            [$term, $normalized],
            static fn (string $value): bool => $value !== '',
        )));
        $columns = array_values(array_filter(
            ['normalized_name', 'name_fa', 'display_name_fa', 'name_en', 'slug', 'code'],
            fn (string $column): bool => $this->hasColumn($model, $column),
        ));

        if ($columns === []) {
            return;
        }

        $query->where(function (Builder $query) use ($model, $columns, $terms): void {
            foreach ($columns as $column) {
                foreach ($terms as $value) {
                    $query->orWhere($model->qualifyColumn($column), 'like', '%'.$value.'%');
                }
            }
        });
    }

    private function applyFallbackStatus(Builder $query, Model $model, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        if ($status === 'inactive' && $this->hasColumn($model, 'is_active')) {
            $query->where($model->qualifyColumn('is_active'), false);

            return;
        }

        if ($status === 'deprecated' && $this->hasColumn($model, 'deprecated_at')) {
            $query->whereNotNull($model->qualifyColumn('deprecated_at'));

            return;
        }

        if ($this->hasColumn($model, 'is_active')) {
            $query->where($model->qualifyColumn('is_active'), true);
        }

        if ($this->hasColumn($model, 'deprecated_at')) {
            $query->whereNull($model->qualifyColumn('deprecated_at'));
        }
    }

    private function applyFallbackSort(Builder $query, Model $model, mixed $sort): void
    {
        $sort = is_string($sort) ? $sort : null;
        $direction = is_string($sort) && str_starts_with($sort, '-') ? 'desc' : 'asc';
        $key = ltrim((string) $sort, '-');
        $column = [
            'code' => 'code',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'name' => 'normalized_name',
        ][$key] ?? 'normalized_name';

        if (! $this->hasColumn($model, $column)) {
            $column = $this->hasColumn($model, 'name_fa') ? 'name_fa' : $model->getKeyName();
        }

        $query->orderBy($model->qualifyColumn($column), $direction)
            ->orderBy($model->getQualifiedKeyName());
    }

    private function hasColumn(Model $model, string $column): bool
    {
        return Schema::hasColumn($model->getTable(), $column);
    }

    private function recordFromModel(string $type, Model $model): LocationRecord
    {
        return new LocationRecord(array_merge($model->getAttributes(), [
            'id' => $model->getKey(),
            'location_type' => $type,
            'dataset' => LocationModelResolver::datasetForLocationType($type),
        ]));
    }
}
