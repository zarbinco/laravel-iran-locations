<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\NeighborhoodApiRequest;
use Zarbin\IranLocations\Http\Resources\NeighborhoodResource;

class NeighborhoodController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(NeighborhoodApiRequest $request): AnonymousResourceCollection
    {
        $query = $this->query('neighborhood')->with(['city', 'defaultRegion', 'defaultArea']);
        $this->applyLocationFilters($query, $request->validated());

        return NeighborhoodResource::collection($this->paginate($query, $request));
    }
}
