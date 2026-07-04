<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class OfficialDistrictIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer'],
            'county_code' => ['nullable', 'string', 'max:255'],
            'has_cities' => ['nullable'],
            'has_rural_districts' => ['nullable'],
        ]);
    }
}
