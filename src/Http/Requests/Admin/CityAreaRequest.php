<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CityAreaRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = $this->commonLocationRules('city_area', 'city_area');
        unset($rules['name_en']);

        return array_merge($rules, [
            'city_region_id' => ['required', 'integer', $this->existsIn('city_region')],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
