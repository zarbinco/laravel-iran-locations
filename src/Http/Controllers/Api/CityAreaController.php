<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityAreaApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\NeighborhoodApiRequest;
use Zarbin\IranLocations\Http\Resources\CityAreaResource;
use Zarbin\IranLocations\Http\Resources\NeighborhoodResource;

class CityAreaController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(CityAreaApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('city_area')->with('region.city');
        $this->applyLocationFilters($query, $request->validated());

        return CityAreaResource::collection($this->paginate($query, $request));
    }

    public function neighborhoods(NeighborhoodApiRequest $request, int|string $area): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('city_area', $area);

        if ($model === null) {
            return $this->missingLocationResponse('City area');
        }

        $query = $this->query('neighborhood')->with(['city', 'defaultRegion', 'defaultArea']);
        $this->applyLocationFilters($query, array_merge($request->validated(), [
            'area_id' => $model->getKey(),
        ]));

        return NeighborhoodResource::collection($this->paginate($query, $request));
    }
}
