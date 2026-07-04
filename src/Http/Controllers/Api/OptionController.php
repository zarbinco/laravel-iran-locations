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
        return $this->optionResponse(
            $this->optionQuery('province', $request->validated()),
            $request,
        );
    }

    public function cities(OptionApiRequest $request): JsonResponse
    {
        return $this->optionResponse(
            $this->optionQuery('city', $request->validated()),
            $request,
        );
    }

    public function cityRegions(OptionApiRequest $request): JsonResponse
    {
        return $this->optionResponse(
            $this->optionQuery('city_region', $request->validated()),
            $request,
        );
    }

    public function cityAreas(OptionApiRequest $request): JsonResponse
    {
        return $this->optionResponse(
            $this->optionQuery('city_area', $request->validated()),
            $request,
        );
    }

    public function neighborhoods(OptionApiRequest $request): JsonResponse
    {
        return $this->optionResponse(
            $this->optionQuery('neighborhood', $request->validated()),
            $request,
        );
    }
}
