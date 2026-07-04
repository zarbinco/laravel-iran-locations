<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeighborhoodResource extends JsonResource
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
            'city_id' => $model->getAttribute('city_id'),
            'city' => $this->whenLoaded('city', fn (): CityResource => new CityResource($model->getRelation('city'))),
            'default_city_region_id' => $model->getAttribute('default_city_region_id'),
            'default_region' => $this->whenLoaded('defaultRegion', fn (): CityRegionResource => new CityRegionResource($model->getRelation('defaultRegion'))),
            'default_city_area_id' => $model->getAttribute('default_city_area_id'),
            'default_area' => $this->whenLoaded('defaultArea', fn (): CityAreaResource => new CityAreaResource($model->getRelation('defaultArea'))),
            'type' => $model->getAttribute('type'),
            'latitude' => $model->getAttribute('latitude'),
            'longitude' => $model->getAttribute('longitude'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'source' => $model->getAttribute('source'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }
}
