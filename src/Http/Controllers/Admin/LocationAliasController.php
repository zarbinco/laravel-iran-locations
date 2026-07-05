<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Http\Requests\Admin\LocationAliasIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\LocationAliasRequest;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAliasController extends AdminController
{
    public function index(LocationAliasIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $filters = $request->validated();
        $query = $this->newModel('location_alias')->newQuery();

        $this->applyFilters($query, $filters);

        return $this->adminView('aliases.index', [
            'aliases' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'locationTypes' => $this->locationTypes(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('aliases.create', [
            'alias' => $this->newModel('location_alias'),
            'locationTypes' => $this->locationTypes(),
        ]);
    }

    public function store(LocationAliasRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $alias = $this->newModel('location_alias');
        $alias->fill($this->aliasPayload($request->validated(), creating: true));
        $alias->save();

        return redirect()
            ->route('iran-locations.admin.aliases.edit', $alias->getKey())
            ->with('status', 'Alias was created.');
    }

    public function edit(int|string $alias): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('aliases.edit', [
            'alias' => $this->findModel('location_alias', $alias),
            'locationTypes' => $this->locationTypes(),
        ]);
    }

    public function update(LocationAliasRequest $request, int|string $alias): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('location_alias', $alias);
        $this->guardPackageRecordDirectEdit($model, 'Alias');
        $model->fill($this->aliasPayload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.aliases.edit', $model->getKey())
            ->with('status', 'Alias was updated.');
    }

    public function destroy(int|string $alias): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('location_alias', $alias), 'Alias');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $raw = trim((string) $filters['q']);
            $normalized = app(LocationNormalizer::class)->search($raw);

            $query->where(function (Builder $query) use ($normalized, $raw): void {
                $query->where('alias', 'like', '%'.$raw.'%')
                    ->orWhere('normalized_alias', 'like', '%'.$normalized.'%');
            });
        }

        if (filled($filters['source'] ?? null) && $filters['source'] !== 'all') {
            $query->where('source', $filters['source']);
        }

        if (filled($filters['location_type'] ?? null)) {
            $query->where('location_type', $this->locationTypeClass((string) $filters['location_type']));
        }

        $sort = (string) ($filters['sort'] ?? '');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, ['alias', 'source', 'created_at', 'updated_at'], true)) {
            $column = 'alias';
        }

        $query->orderBy($column, $direction)->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function aliasPayload(array $data, bool $creating = false): array
    {
        $data['location_type'] = $this->locationTypeClass((string) $data['location_type']);

        return $this->payload($data, $creating);
    }

    private function locationTypeClass(string $type): string
    {
        return LocationModelResolver::model($type);
    }

    /**
     * @return array<string, string>
     */
    private function locationTypes(): array
    {
        return [
            'province' => 'Province',
            'city' => 'City',
            'city_region' => 'City region',
            'city_area' => 'City area',
            'neighborhood' => 'Neighborhood',
        ];
    }
}
