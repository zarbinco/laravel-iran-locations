<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\OfficialDistrictBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\OfficialDistrictIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\OfficialDistrictRequest;

class OfficialDistrictController extends AdminController
{
    public function index(OfficialDistrictIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('official_district')->newQuery()->with(['province', 'county']);

        if ($query instanceof OfficialDistrictBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('official-districts.index', [
            'officialDistricts' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('official-districts.create', [
            'officialDistrict' => $this->newModel('official_district'),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
        ]);
    }

    public function store(OfficialDistrictRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $officialDistrict = $this->newModel('official_district');
        $officialDistrict->fill($this->payload($request->validated(), creating: true));
        $officialDistrict->save();

        return redirect()
            ->route('iran-locations.admin.official-districts.edit', $officialDistrict->getKey())
            ->with('status', 'Official district was created.');
    }

    public function edit(int|string $official_district): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('official-districts.edit', [
            'officialDistrict' => $this->findModel('official_district', $official_district),
            'provinces' => $this->optionRecords('province'),
            'counties' => $this->optionRecords('county'),
        ]);
    }

    public function update(OfficialDistrictRequest $request, int|string $official_district): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('official_district', $official_district);
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.official-districts.edit', $model->getKey())
            ->with('status', 'Official district was updated.');
    }

    public function destroy(int|string $official_district): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('official_district', $official_district), 'Official district');
    }
}
