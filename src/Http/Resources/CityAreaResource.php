<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityAreaResource extends JsonResource
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
            'slug' => $model->getAttribute('slug'),
            'city_region_id' => $model->getAttribute('city_region_id'),
            'region' => $this->whenLoaded('region', fn (): CityRegionResource => new CityRegionResource($model->getRelation('region'))),
            'number' => $model->getAttribute('number'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'source' => $model->getAttribute('source'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }
}
