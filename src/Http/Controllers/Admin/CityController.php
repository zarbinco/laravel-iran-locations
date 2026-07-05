<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\CityBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\CityIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\CityRequest;

class CityController extends AdminController
{
    public function index(CityIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('city')->newQuery();

        if ($query instanceof CityBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('cities.index', [
            'cities' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('cities.create', [
            'city' => $this->newModel('city'),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function store(CityRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $city = $this->newModel('city');
        $city->fill($this->payload($request->validated(), creating: true));
        $city->save();

        return redirect()
            ->route('iran-locations.admin.cities.edit', $city->getKey())
            ->with('status', 'City was created.');
    }

    public function edit(int|string $city): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('cities.edit', [
            'city' => $this->findModel('city', $city),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function update(CityRequest $request, int|string $city): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('city', $city);
        $this->guardPackageRecordDirectEdit($model, 'City');
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.cities.edit', $model->getKey())
            ->with('status', 'City was updated.');
    }

    public function destroy(int|string $city): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('city', $city), 'City');
    }
}
