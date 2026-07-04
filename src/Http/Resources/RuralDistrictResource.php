<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RuralDistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        if (! $model instanceof Model) {
            return [];
        }

        return [
            'id' => $model->getKey(),
            'code' => $model->getAttribute('code'),
            'name_fa' => $model->getAttribute('name_fa'),
            'display_name_fa' => $model->getAttribute('display_name_fa') ?: $model->getAttribute('name_fa'),
            'name_en' => $model->getAttribute('name_en'),
            'slug' => $model->getAttribute('slug'),
            'province_id' => $model->getAttribute('province_id'),
            'county_id' => $model->getAttribute('county_id'),
            'official_district_id' => $model->getAttribute('official_district_id'),
            'province' => $this->whenLoaded('province', fn (): ProvinceResource => new ProvinceResource($model->getRelation('province'))),
            'county' => $this->whenLoaded('county', fn (): CountyResource => new CountyResource($model->getRelation('county'))),
            'official_district' => $this->whenLoaded('officialDistrict', fn (): OfficialDistrictResource => new OfficialDistrictResource($model->getRelation('officialDistrict'))),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'source' => $model->getAttribute('source'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }
}
