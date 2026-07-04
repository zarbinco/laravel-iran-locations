<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'location_type' => $model->getAttribute('location_type'),
            'location_id' => $model->getAttribute('location_id'),
            'alias' => $model->getAttribute('alias'),
            'normalized_alias' => $model->getAttribute('normalized_alias'),
            'reason' => $model->getAttribute('reason'),
            'source' => $model->getAttribute('source'),
        ];
    }
}
