<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Zarbin\IranLocations\Builders\LocationBuilder;
use Zarbin\IranLocations\Http\Requests\Api\ApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\OptionApiRequest;
use Zarbin\IranLocations\Http\Resources\LocationOptionResource;
use Zarbin\IranLocations\Support\LocationModelResolver;

trait ResolvesLocationApiModels
{
    /**
     * @return class-string<Model>
     */
    protected function modelClass(string $key): string
    {
        /** @var class-string<Model> $class */
        $class = LocationModelResolver::model($key);

        return $class;
    }

    protected function newModel(string $key): Model
    {
        $class = $this->modelClass($key);

        return new $class;
    }

    protected function query(string $key): Builder
    {
        return $this->newModel($key)->newQuery();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyLocationFilters(Builder $query, array $filters): Builder
    {
        $model = $query->getModel();
        $filters = $this->filtersWithDefaultStatus($filters);

        if ($query instanceof LocationBuilder) {
            $query->filter($filters);

            if (! array_key_exists('sort', $filters)) {
                $query->ordered();
            }

            return $query;
        }

        $this->applyFallbackStatus($query, $model, (string) ($filters['status'] ?? 'active'));
        $this->applyFallbackCommonFilters($query, $model, $filters);
        $this->applyFallbackSort($query, $model, $filters['sort'] ?? null);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function filtersWithDefaultStatus(array $filters): array
    {
        if (! array_key_exists('status', $filters) || blank($filters['status'])) {
            $filters['status'] = 'active';
        }

        return $filters;
    }

    protected function paginate(Builder $query, ApiRequest $request): mixed
    {
        return $query->paginate($request->perPage())->withQueryString();
    }

    protected function resolveLocation(string $key, int|string $value): ?Model
    {
        $model = $this->newModel($key);
        $query = $model->newQuery();

        $query->where(function (Builder $query) use ($model, $value): void {
            if (is_numeric($value)) {
                $query->whereKey((int) $value);
            }

            if ($this->hasColumn($model, 'code')) {
                $query->orWhere($model->qualifyColumn('code'), (string) $value);
            }

            if ($this->hasColumn($model, 'slug')) {
                $query->orWhere($model->qualifyColumn('slug'), (string) $value);
            }
        });

        if ($query instanceof LocationBuilder) {
            $query->active();
        } else {
            $this->applyFallbackStatus($query, $model, 'active');
        }

        return $query->first();
    }

    protected function missingLocationResponse(string $label = 'Location'): JsonResponse
    {
        return response()->json(['message' => "{$label} not found."], 404);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $expected
     */
    protected function nestedFilterConflictResponse(array $filters, array $expected): ?JsonResponse
    {
        $errors = [];

        foreach ($expected as $field => $value) {
            if (! filled($filters[$field] ?? null)) {
                continue;
            }

            if ((string) $filters[$field] === (string) $value) {
                continue;
            }

            $errors[$field] = ["The selected {$field} conflicts with the route parent."];
        }

        if ($errors === []) {
            return null;
        }

        return response()->json([
            'message' => 'The selected parent filter conflicts with the route parent.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function optionQuery(string $key, array $filters = []): Builder
    {
        return $this->applyLocationFilters($this->query($key), $filters);
    }

    protected function optionResponse(Builder $query, OptionApiRequest $request): JsonResponse
    {
        $records = $query->limit($request->limit())->get();

        return response()->json(LocationOptionResource::collection($records)->resolve($request));
    }

    /**
     * @template TResource of JsonResource
     *
     * @param  class-string<TResource>  $resource
     * @return array<int, array<string, mixed>>
     */
    protected function resourceArray(string $resource, mixed $records, ApiRequest $request): array
    {
        /** @var array<int, array<string, mixed>> $resolved */
        $resolved = $resource::collection($records)->resolve($request);

        return $resolved;
    }

    protected function locationTypeClass(string $type): string
    {
        return LocationModelResolver::model($type);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFallbackCommonFilters(Builder $query, Model $model, array $filters): void
    {
        if (filled($filters['source'] ?? null) && $filters['source'] !== 'all' && $this->hasColumn($model, 'source')) {
            $query->where($model->qualifyColumn('source'), $filters['source']);
        }

        if (filled($filters['code'] ?? null) && $this->hasColumn($model, 'code')) {
            $query->where($model->qualifyColumn('code'), $filters['code']);
        }

        if (filled($filters['slug'] ?? null) && $this->hasColumn($model, 'slug')) {
            $query->where($model->qualifyColumn('slug'), $filters['slug']);
        }
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
}
