<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class NeighborhoodRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('neighborhood', 'neighborhood'), [
            'city_id' => ['required', 'integer', $this->existsIn('city')],
            'default_city_region_id' => ['nullable', 'integer', $this->existsIn('city_region')],
            'default_city_area_id' => ['nullable', 'integer', $this->existsIn('city_area')],
            'type' => ['required', 'in:neighborhood,street,boulevard,square,highway,park,area,unknown'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
    }
}
