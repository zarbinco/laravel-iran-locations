<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\ProvinceBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\ProvinceIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\ProvinceRequest;

class ProvinceController extends AdminController
{
    public function index(ProvinceIndexRequest $request): View
    {
        $this->authorizeIranLocationsAdmin();

        $query = $this->newModel('province')->newQuery();

        if ($query instanceof ProvinceBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('provinces.index', [
            'provinces' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('provinces.create', [
            'province' => $this->newModel('province'),
        ]);
    }

    public function store(ProvinceRequest $request): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $province = $this->newModel('province');
        $province->fill($this->payload($request->validated(), creating: true));
        $province->save();

        return redirect()
            ->route('iran-locations.admin.provinces.edit', $province->getKey())
            ->with('status', 'Province was created.');
    }

    public function edit(int|string $province): View
    {
        $this->authorizeIranLocationsAdmin();

        return $this->adminView('provinces.edit', [
            'province' => $this->findModel('province', $province),
        ]);
    }

    public function update(ProvinceRequest $request, int|string $province): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        $model = $this->findModel('province', $province);
        $this->guardPackageRecordDirectEdit($model, 'Province');
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.provinces.edit', $model->getKey())
            ->with('status', 'Province was updated.');
    }

    public function destroy(int|string $province): RedirectResponse
    {
        $this->authorizeIranLocationsAdmin();

        return $this->safeDestroy($this->findModel('province', $province), 'Province');
    }
}
