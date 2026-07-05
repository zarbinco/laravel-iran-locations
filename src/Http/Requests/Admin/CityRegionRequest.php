<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CityRegionRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('city_region', 'city_region'), [
            'city_id' => $this->requiredIdRules('city'),
            'number' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
