<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\CityRegionBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\CityRegionIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\CityRegionRequest;

class CityRegionController extends AdminController
{
    public function index(CityRegionIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('city_region')->newQuery();

        if ($query instanceof CityRegionBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('city-regions.index', [
            'regions' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'cities' => $this->optionRecords('city'),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('city-regions.create', [
            'region' => $this->newModel('city_region'),
            'cities' => $this->optionRecords('city'),
        ]);
    }

    public function store(CityRegionRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $region = $this->newModel('city_region');
        $region->fill($this->payload($request->validated(), creating: true));
        $region->save();

        return redirect()
            ->route('iran-locations.admin.city-regions.edit', $region->getKey())
            ->with('status', 'City region was created.');
    }

    public function edit(int|string $city_region): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('city-regions.edit', [
            'region' => $this->findModel('city_region', $city_region),
            'cities' => $this->optionRecords('city'),
        ]);
    }

    public function update(CityRegionRequest $request, int|string $city_region): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('city_region', $city_region);
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.city-regions.edit', $model->getKey())
            ->with('status', 'City region was updated.');
    }

    public function destroy(int|string $city_region): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('city_region', $city_region), 'City region');
    }
}
