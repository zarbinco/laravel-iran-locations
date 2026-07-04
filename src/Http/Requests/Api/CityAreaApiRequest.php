<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class CityAreaApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'city_id' => ['nullable', 'integer'],
            'city_code' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
