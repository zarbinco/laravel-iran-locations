<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\OptionApiRequest;

class OptionController extends Controller
{
    use ResolvesLocationApiModels;

    public function provinces(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('province', $request);
    }

    public function cities(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('city', $request);
    }

    public function counties(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('county', $request);
    }

    public function officialDistricts(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('official_district', $request);
    }

    public function ruralDistricts(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('rural_district', $request);
    }

    public function cityRegions(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('city_region', $request);
    }

    public function cityAreas(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('city_area', $request);
    }

    public function neighborhoods(OptionApiRequest $request): JsonResponse
    {
        return $this->readOptionResponse('neighborhood', $request);
    }
}
