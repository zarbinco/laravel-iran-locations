<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityRegionResource extends JsonResource
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
            'city_id' => $model->getAttribute('city_id'),
            'city' => $this->whenLoaded('city', fn (): CityResource => new CityResource($model->getRelation('city'))),
            'number' => $model->getAttribute('number'),
            'type' => $model->getAttribute('type'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'source' => $model->getAttribute('source'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }
}
