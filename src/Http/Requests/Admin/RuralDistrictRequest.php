<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class RuralDistrictRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('rural_district', 'rural_district'), [
            'province_id' => ['required', 'integer', $this->existsIn('province')],
            'county_id' => ['required', 'integer', $this->existsIn('county')],
            'official_district_id' => ['required', 'integer', $this->existsIn('official_district')],
        ]);
    }
}
