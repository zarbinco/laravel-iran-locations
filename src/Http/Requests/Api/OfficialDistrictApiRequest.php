<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class OfficialDistrictApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer'],
            'county_code' => ['nullable', 'string', 'max:255'],
            'has_cities' => ['nullable', 'boolean'],
            'has_rural_districts' => ['nullable', 'boolean'],
            'include_counts' => ['nullable', 'boolean'],
        ]);
    }
}
