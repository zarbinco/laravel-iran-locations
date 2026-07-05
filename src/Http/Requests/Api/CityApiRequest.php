<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class CityApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'province_id' => ['nullable', 'integer', 'min:1'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer', 'min:1'],
            'county_code' => ['nullable', 'string', 'max:255'],
            'official_district_id' => ['nullable', 'integer', 'min:1'],
            'official_district_code' => ['nullable', 'string', 'max:255'],
            'is_capital' => ['nullable', 'boolean'],
            'has_regions' => ['nullable', 'boolean'],
            'has_neighborhoods' => ['nullable', 'boolean'],
            'include_counts' => ['nullable', 'boolean'],
        ]);
    }
}
