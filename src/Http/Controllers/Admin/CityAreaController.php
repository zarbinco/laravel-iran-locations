<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\CityAreaBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\CityAreaIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\CityAreaRequest;

class CityAreaController extends AdminController
{
    public function index(CityAreaIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('city_area')->newQuery();

        if ($query instanceof CityAreaBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('city-areas.index', [
            'areas' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'regions' => $this->optionRecords('city_region'),
            'cities' => $this->optionRecords('city'),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('city-areas.create', [
            'area' => $this->newModel('city_area'),
            'regions' => $this->optionRecords('city_region'),
        ]);
    }

    public function store(CityAreaRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $area = $this->newModel('city_area');
        $area->fill($this->payload($request->validated(), creating: true));
        $area->save();

        return redirect()
            ->route('iran-locations.admin.city-areas.edit', $area->getKey())
            ->with('status', 'City area was created.');
    }

    public function edit(int|string $city_area): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('city-areas.edit', [
            'area' => $this->findModel('city_area', $city_area),
            'regions' => $this->optionRecords('city_region'),
        ]);
    }

    public function update(CityAreaRequest $request, int|string $city_area): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('city_area', $city_area);
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.city-areas.edit', $model->getKey())
            ->with('status', 'City area was updated.');
    }

    public function destroy(int|string $city_area): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('city_area', $city_area), 'City area');
    }
}
