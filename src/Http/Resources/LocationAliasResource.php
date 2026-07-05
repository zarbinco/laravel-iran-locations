<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAliasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        if (! $model instanceof Model) {
            return [];
        }

        return [
            'id' => $model->getKey(),
            'location_type' => $this->locationType($model),
            'location_id' => $model->getAttribute('location_id'),
            'alias' => $model->getAttribute('alias'),
            'normalized_alias' => $model->getAttribute('normalized_alias'),
            'reason' => $model->getAttribute('reason'),
            'source' => $model->getAttribute('source'),
        ];
    }

    private function locationType(Model $model): string
    {
        $type = (string) $model->getAttribute('location_type');

        if (class_exists($type)) {
            return LocationModelResolver::locationTypeForModel($type);
        }

        return LocationModelResolver::normalizeLocationType($type);
    }
}
