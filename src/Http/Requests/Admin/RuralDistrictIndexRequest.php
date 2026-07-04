<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class RuralDistrictIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'county_id' => ['nullable', 'integer'],
            'county_code' => ['nullable', 'string', 'max:255'],
            'official_district_id' => ['nullable', 'integer'],
            'official_district_code' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
