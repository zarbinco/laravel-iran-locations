<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class CountyApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'province_id' => ['nullable', 'integer', 'min:1'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'has_cities' => ['nullable', 'boolean'],
            'has_official_districts' => ['nullable', 'boolean'],
            'include_counts' => ['nullable', 'boolean'],
        ]);
    }
}
