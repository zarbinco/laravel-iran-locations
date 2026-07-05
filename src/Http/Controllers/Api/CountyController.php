<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\CountyApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\OfficialDistrictApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\RuralDistrictApiRequest;
use Zarbin\IranLocations\Http\Resources\CityResource;
use Zarbin\IranLocations\Http\Resources\CountyResource;
use Zarbin\IranLocations\Http\Resources\OfficialDistrictResource;
use Zarbin\IranLocations\Http\Resources\RuralDistrictResource;

class CountyController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(CountyApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('county')->with('province');
        $this->applyLocationFilters($query, $request->validated());

        return CountyResource::collection($this->paginate($query, $request));
    }

    public function officialDistricts(OfficialDistrictApiRequest $request, int|string $county): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('county', $county);

        if ($model === null) {
            return $this->missingLocationResponse('County');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'county_id' => $model->getKey(),
            'county_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('official_district')->with(['province', 'county']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'county_id' => $model->getKey(),
        ]));

        return OfficialDistrictResource::collection($this->paginate($query, $request));
    }

    public function cities(CityApiRequest $request, int|string $county): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('county', $county);

        if ($model === null) {
            return $this->missingLocationResponse('County');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'county_id' => $model->getKey(),
            'county_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('city')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'county_id' => $model->getKey(),
        ]));

        return CityResource::collection($this->paginate($query, $request));
    }

    public function ruralDistricts(RuralDistrictApiRequest $request, int|string $county): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('county', $county);

        if ($model === null) {
            return $this->missingLocationResponse('County');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'county_id' => $model->getKey(),
            'county_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('rural_district')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'county_id' => $model->getKey(),
        ]));

        return RuralDistrictResource::collection($this->paginate($query, $request));
    }
}
