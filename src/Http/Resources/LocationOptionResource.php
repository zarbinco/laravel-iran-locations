<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        if (! $model instanceof Model) {
            return [];
        }

        $label = $model->getAttribute('display_name_fa') ?: $model->getAttribute('name_fa');

        return [
            'value' => $model->getKey(),
            'code' => $model->getAttribute('code'),
            'label' => $label,
            'name_fa' => $model->getAttribute('name_fa'),
        ];
    }
}
