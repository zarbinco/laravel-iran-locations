<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class CityRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('city', 'city'), [
            'province_id' => $this->requiredIdRules('province'),
            'county_id' => $this->nullableIdRules('county'),
            'official_district_id' => $this->nullableIdRules('official_district'),
            'is_province_capital' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->parentMatches('county', $this->input('county_id'), 'province_id', $this->input('province_id'))) {
                    $this->addHierarchyError($validator, 'county_id', 'The selected county does not belong to the selected province.');
                }

                if (! $this->parentMatches('official_district', $this->input('official_district_id'), 'province_id', $this->input('province_id'))) {
                    $this->addHierarchyError($validator, 'official_district_id', 'The selected official district does not belong to the selected province.');
                }

                if (! $this->parentMatches('official_district', $this->input('official_district_id'), 'county_id', $this->input('county_id'))) {
                    $this->addHierarchyError($validator, 'official_district_id', 'The selected official district does not belong to the selected county.');
                }
            },
        ];
    }
}
