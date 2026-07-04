<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\CityAreaApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\CityRegionApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\NeighborhoodApiRequest;
use Zarbin\IranLocations\Http\Resources\CityAreaResource;
use Zarbin\IranLocations\Http\Resources\CityRegionResource;
use Zarbin\IranLocations\Http\Resources\CityResource;
use Zarbin\IranLocations\Http\Resources\NeighborhoodResource;

class CityController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(CityApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('city')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, $request->validated());

        return CityResource::collection($this->paginate($query, $request));
    }

    public function regions(CityRegionApiRequest $request, int|string $city): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('city', $city);

        if ($model === null) {
            return $this->missingLocationResponse('City');
        }

        $query = $this->query('city_region')->with('city');
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'city_id' => $model->getKey(),
        ]));

        return CityRegionResource::collection($this->paginate($query, $request));
    }

    public function areas(CityAreaApiRequest $request, int|string $city): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('city', $city);

        if ($model === null) {
            return $this->missingLocationResponse('City');
        }

        $query = $this->query('city_area')->with('region.city');
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'city_id' => $model->getKey(),
        ]));

        return CityAreaResource::collection($this->paginate($query, $request));
    }

    public function neighborhoods(NeighborhoodApiRequest $request, int|string $city): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('city', $city);

        if ($model === null) {
            return $this->missingLocationResponse('City');
        }

        $query = $this->query('neighborhood')->with(['city', 'defaultRegion', 'defaultArea']);
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'city_id' => $model->getKey(),
        ]));

        return NeighborhoodResource::collection($this->paginate($query, $request));
    }
}
