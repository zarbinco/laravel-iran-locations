<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class ProvinceApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'has_cities' => ['nullable', 'boolean'],
            'include_counts' => ['nullable', 'boolean'],
        ]);
    }
}
