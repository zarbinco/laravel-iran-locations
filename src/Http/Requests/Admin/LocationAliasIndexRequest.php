<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class LocationAliasIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:'.$this->searchMinLength(), 'max:255'],
            'source' => ['nullable', 'in:package,custom,all'],
            'location_type' => ['nullable', 'in:province,city,city_region,city_area,neighborhood'],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
