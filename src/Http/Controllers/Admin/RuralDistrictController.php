<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\RuralDistrictBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\RuralDistrictIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\RuralDistrictRequest;

class RuralDistrictController extends AdminController
{
    public function index(RuralDistrictIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('rural_district')->newQuery()->with(['province', 'county', 'officialDistrict']);

        if ($query instanceof RuralDistrictBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('rural-districts.index', [
            'ruralDistricts' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('rural-districts.create', [
            'ruralDistrict' => $this->newModel('rural_district'),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function store(RuralDistrictRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $ruralDistrict = $this->newModel('rural_district');
        $ruralDistrict->fill($this->payload($request->validated(), creating: true));
        $ruralDistrict->save();

        return redirect()
            ->route('iran-locations.admin.rural-districts.edit', $ruralDistrict->getKey())
            ->with('status', 'Rural district was created.');
    }

    public function edit(int|string $rural_district): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('rural-districts.edit', [
            'ruralDistrict' => $this->findModel('rural_district', $rural_district),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
            'officialDistricts' => $this->optionRecords('official_district'),
        ]);
    }

    public function update(RuralDistrictRequest $request, int|string $rural_district): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('rural_district', $rural_district);
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.rural-districts.edit', $model->getKey())
            ->with('status', 'Rural district was updated.');
    }

    public function destroy(int|string $rural_district): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('rural_district', $rural_district), 'Rural district');
    }
}
