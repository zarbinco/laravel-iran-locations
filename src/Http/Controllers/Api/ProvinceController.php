<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\CountyApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\ProvinceApiRequest;
use Zarbin\IranLocations\Http\Resources\CityResource;
use Zarbin\IranLocations\Http\Resources\CountyResource;
use Zarbin\IranLocations\Http\Resources\ProvinceResource;

class ProvinceController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(ProvinceApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('province');
        $this->applyLocationFilters($query, $request->validated());

        return ProvinceResource::collection($this->paginate($query, $request));
    }

    public function cities(CityApiRequest $request, int|string $province): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('province', $province);

        if ($model === null) {
            return $this->missingLocationResponse('Province');
        }

        $query = $this->query('city')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'province_id' => $model->getKey(),
        ]));

        return CityResource::collection($this->paginate($query, $request));
    }

    public function counties(CountyApiRequest $request, int|string $province): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('province', $province);

        if ($model === null) {
            return $this->missingLocationResponse('Province');
        }

        $query = $this->query('county')->with('province');
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'province_id' => $model->getKey(),
        ]));

        return CountyResource::collection($this->paginate($query, $request));
    }
}
