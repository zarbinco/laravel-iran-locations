<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class OfficialDistrictRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('official_district', 'official_district'), [
            'province_id' => ['required', 'integer', $this->existsIn('province')],
            'county_id' => ['required', 'integer', $this->existsIn('county')],
        ]);
    }
}
