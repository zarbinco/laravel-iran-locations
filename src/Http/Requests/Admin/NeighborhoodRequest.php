<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class NeighborhoodRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('neighborhood', 'neighborhood'), [
            'city_id' => $this->requiredIdRules('city'),
            'default_city_region_id' => $this->nullableIdRules('city_region'),
            'default_city_area_id' => $this->nullableIdRules('city_area'),
            'type' => ['required', 'in:neighborhood,street,boulevard,square,highway,park,area,unknown'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->parentMatches('city_region', $this->input('default_city_region_id'), 'city_id', $this->input('city_id'))) {
                    $this->addHierarchyError($validator, 'default_city_region_id', 'The selected default region does not belong to the selected city.');
                }

                $areaRegionId = $this->modelColumnValue('city_area', $this->input('default_city_area_id'), 'city_region_id');

                if ($areaRegionId !== null && ! $this->parentMatches('city_region', $areaRegionId, 'city_id', $this->input('city_id'))) {
                    $this->addHierarchyError($validator, 'default_city_area_id', 'The selected default area does not belong to the selected city.');
                }

                if (! blank($this->input('default_city_region_id')) && $areaRegionId !== null && (string) $areaRegionId !== (string) $this->input('default_city_region_id')) {
                    $this->addHierarchyError($validator, 'default_city_area_id', 'The selected default area does not belong to the selected default region.');
                }
            },
        ];
    }
}
