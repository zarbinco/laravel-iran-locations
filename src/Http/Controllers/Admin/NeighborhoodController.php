<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\NeighborhoodBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\NeighborhoodIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\NeighborhoodRequest;

class NeighborhoodController extends AdminController
{
    public function index(NeighborhoodIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('neighborhood')->newQuery();

        if ($query instanceof NeighborhoodBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('neighborhoods.index', [
            'neighborhoods' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'provinces' => $this->optionRecords('province'),
            'cities' => $this->optionRecords('city'),
            'regions' => $this->optionRecords('city_region'),
            'areas' => $this->optionRecords('city_area'),
            'types' => $this->types(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('neighborhoods.create', [
            'neighborhood' => $this->newModel('neighborhood'),
            'cities' => $this->optionRecords('city'),
            'regions' => $this->optionRecords('city_region'),
            'areas' => $this->optionRecords('city_area'),
            'types' => $this->types(),
        ]);
    }

    public function store(NeighborhoodRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $neighborhood = $this->newModel('neighborhood');
        $neighborhood->fill($this->payload($request->validated(), creating: true));
        $neighborhood->save();

        return redirect()
            ->route('iran-locations.admin.neighborhoods.edit', $neighborhood->getKey())
            ->with('status', 'Neighborhood was created.');
    }

    public function edit(int|string $neighborhood): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('neighborhoods.edit', [
            'neighborhood' => $this->findModel('neighborhood', $neighborhood),
            'cities' => $this->optionRecords('city'),
            'regions' => $this->optionRecords('city_region'),
            'areas' => $this->optionRecords('city_area'),
            'types' => $this->types(),
        ]);
    }

    public function update(NeighborhoodRequest $request, int|string $neighborhood): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('neighborhood', $neighborhood);
        $this->guardPackageRecordDirectEdit($model, 'Neighborhood');
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.neighborhoods.edit', $model->getKey())
            ->with('status', 'Neighborhood was updated.');
    }

    public function destroy(int|string $neighborhood): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('neighborhood', $neighborhood), 'Neighborhood');
    }

    /**
     * @return array<int, string>
     */
    private function types(): array
    {
        return ['neighborhood', 'street', 'boulevard', 'square', 'highway', 'park', 'area', 'unknown'];
    }
}
