<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class NeighborhoodApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer'],
            'city_code' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'integer'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:neighborhood,street,boulevard,square,highway,park,area,unknown,all'],
            'has_region' => ['nullable', 'boolean'],
            'missing_region' => ['nullable', 'boolean'],
        ]);
    }
}
