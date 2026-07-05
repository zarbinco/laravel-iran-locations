<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class OfficialDistrictRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('official_district', 'official_district'), [
            'province_id' => $this->requiredIdRules('province'),
            'county_id' => $this->requiredIdRules('county'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->parentMatches('county', $this->input('county_id'), 'province_id', $this->input('province_id'))) {
                    $this->addHierarchyError($validator, 'county_id', 'The selected county does not belong to the selected province.');
                }
            },
        ];
    }
}
