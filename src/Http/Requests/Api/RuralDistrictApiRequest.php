<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class RuralDistrictApiRequest extends ApiRequest
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
            'include_counts' => ['nullable', 'boolean'],
        ]);
    }
}
