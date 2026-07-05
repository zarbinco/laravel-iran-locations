<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\RuralDistrictApiRequest;
use Zarbin\IranLocations\Http\Resources\RuralDistrictResource;

class RuralDistrictController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(RuralDistrictApiRequest $request): AnonymousResourceCollection|JsonResponse
    {
        if ($this->usesJsonReadRepository()) {
            return $this->readCollectionResponse('rural_district', $request);
        }

        $query = $this->query('rural_district')->with(['province', 'county', 'officialDistrict']);
        $this->applyLocationFilters($query, $request->validated());

        return RuralDistrictResource::collection($this->paginate($query, $request));
    }
}
