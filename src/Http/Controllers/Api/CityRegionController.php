<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityAreaApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\CityRegionApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\NeighborhoodApiRequest;
use Zarbin\IranLocations\Http\Resources\CityAreaResource;
use Zarbin\IranLocations\Http\Resources\CityRegionResource;
use Zarbin\IranLocations\Http\Resources\NeighborhoodResource;

class CityRegionController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(CityRegionApiRequest $request): AnonymousResourceCollection|JsonResponse
    {
        if ($this->usesJsonReadRepository()) {
            return $this->readCollectionResponse('city_region', $request);
        }

        $query = $this->query('city_region')->with('city');
        $this->applyLocationFilters($query, $request->validated());

        return CityRegionResource::collection($this->paginate($query, $request));
    }

    public function areas(CityAreaApiRequest $request, int|string $region): AnonymousResourceCollection|JsonResponse
    {
        if ($this->usesJsonReadRepository()) {
            return $this->readNestedCollectionResponse('city_region', $region, 'city_area', 'region_code', $request, 'City region');
        }

        $model = $this->resolveLocation('city_region', $region);

        if ($model === null) {
            return $this->missingLocationResponse('City region');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'region_id' => $model->getKey(),
            'region_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('city_area')->with('region.city');
        $this->applyLocationFilters($query, array_merge($filters, [
            'region_id' => $model->getKey(),
        ]));

        return CityAreaResource::collection($this->paginate($query, $request));
    }

    public function neighborhoods(NeighborhoodApiRequest $request, int|string $region): AnonymousResourceCollection|JsonResponse
    {
        if ($this->usesJsonReadRepository()) {
            return $this->readNestedCollectionResponse('city_region', $region, 'neighborhood', 'region_code', $request, 'City region');
        }

        $model = $this->resolveLocation('city_region', $region);

        if ($model === null) {
            return $this->missingLocationResponse('City region');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'region_id' => $model->getKey(),
            'region_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('neighborhood')->with(['city', 'defaultRegion', 'defaultArea']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'region_id' => $model->getKey(),
        ]));

        return NeighborhoodResource::collection($this->paginate($query, $request));
    }
}
