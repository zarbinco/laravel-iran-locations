<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Zarbin\IranLocations\Builders\CountyBuilder;
use Zarbin\IranLocations\Http\Requests\Admin\CountyIndexRequest;
use Zarbin\IranLocations\Http\Requests\Admin\CountyRequest;

class CountyController extends AdminController
{
    public function index(CountyIndexRequest $request): View
    {
        $query = $this->newModel('county')->newQuery()->with('province');

        if ($query instanceof CountyBuilder) {
            $query->filter($request->validated());
        }

        return $this->adminView('counties.index', [
            'counties' => $query->paginate($this->perPage($request->integer('per_page')))->withQueryString(),
            'provinces' => $this->optionRecords('province'),
        ]);
    }

    public function create(): View
    {
        return $this->adminView('counties.create', [
            'county' => $this->newModel('county'),
            'provinces' => $this->optionRecords('province'),
        ]);
    }

    public function store(CountyRequest $request): RedirectResponse
    {
        $county = $this->newModel('county');
        $county->fill($this->payload($request->validated(), creating: true));
        $county->save();

        return redirect()
            ->route('iran-locations.admin.counties.edit', $county->getKey())
            ->with('status', 'County was created.');
    }

    public function edit(int|string $county): View
    {
        return $this->adminView('counties.edit', [
            'county' => $this->findModel('county', $county),
            'provinces' => $this->optionRecords('province'),
        ]);
    }

    public function update(CountyRequest $request, int|string $county): RedirectResponse
    {
        $model = $this->findModel('county', $county);
        $this->guardPackageRecordDirectEdit($model, 'County');
        $model->fill($this->payload($request->validated()));
        $model->save();

        return redirect()
            ->route('iran-locations.admin.counties.edit', $model->getKey())
            ->with('status', 'County was updated.');
    }

    public function destroy(int|string $county): RedirectResponse
    {
        return $this->safeDestroy($this->findModel('county', $county), 'County');
    }
}
