<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class RuralDistrictRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('rural_district', 'rural_district'), [
            'province_id' => $this->requiredIdRules('province'),
            'county_id' => $this->requiredIdRules('county'),
            'official_district_id' => $this->requiredIdRules('official_district'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->parentMatches('county', $this->input('county_id'), 'province_id', $this->input('province_id'))) {
                    $this->addHierarchyError($validator, 'county_id', 'The selected county does not belong to the selected province.');
                }

                if (! $this->parentMatches('official_district', $this->input('official_district_id'), 'county_id', $this->input('county_id'))) {
                    $this->addHierarchyError($validator, 'official_district_id', 'The selected official district does not belong to the selected county.');
                }

                if (! $this->parentMatches('official_district', $this->input('official_district_id'), 'province_id', $this->input('province_id'))) {
                    $this->addHierarchyError($validator, 'official_district_id', 'The selected official district does not belong to the selected province.');
                }
            },
        ];
    }
}
