<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\CityApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\OfficialDistrictApiRequest;
use Zarbin\IranLocations\Http\Requests\Api\RuralDistrictApiRequest;
use Zarbin\IranLocations\Http\Resources\CityResource;
use Zarbin\IranLocations\Http\Resources\OfficialDistrictResource;
use Zarbin\IranLocations\Http\Resources\RuralDistrictResource;

class OfficialDistrictController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(OfficialDistrictApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('official_district')->with(['province', 'county']);
        $this->applyLocationFilters($query, $request->validated());

        return OfficialDistrictResource::collection($this->paginate($query, $request));
    }

    public function cities(CityApiRequest $request, int|string $officialDistrict): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('official_district', $officialDistrict);

        if ($model === null) {
            return $this->missingLocationResponse('Official district');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'official_district_id' => $model->getKey(),
            'official_district_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('city')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'official_district_id' => $model->getKey(),
        ]));

        return CityResource::collection($this->paginate($query, $request));
    }

    public function ruralDistricts(RuralDistrictApiRequest $request, int|string $officialDistrict): AnonymousResourceCollection|JsonResponse
    {
        $model = $this->resolveLocation('official_district', $officialDistrict);

        if ($model === null) {
            return $this->missingLocationResponse('Official district');
        }

        $filters = $request->validated();
        $conflict = $this->nestedFilterConflictResponse($filters, [
            'official_district_id' => $model->getKey(),
            'official_district_code' => $model->getAttribute('code'),
        ]);

        if ($conflict !== null) {
            return $conflict;
        }

        $query = $this->query('rural_district')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, array_merge($filters, [
            'official_district_id' => $model->getKey(),
        ]));

        return RuralDistrictResource::collection($this->paginate($query, $request));
    }
}
