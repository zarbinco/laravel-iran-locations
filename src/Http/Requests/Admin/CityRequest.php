<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CityRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('city', 'city'), [
            'province_id' => ['required', 'integer', $this->existsIn('province')],
            'county_id' => ['nullable', 'integer', $this->existsIn('county')],
            'official_district_id' => ['nullable', 'integer', $this->existsIn('official_district')],
            'is_province_capital' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
    }
}
