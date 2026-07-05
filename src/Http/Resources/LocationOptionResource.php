<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zarbin\IranLocations\Support\LocationRecord;

class LocationOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        if ($model instanceof LocationRecord) {
            return $model->option();
        }

        if (! $model instanceof Model) {
            return [];
        }

        $label = $model->getAttribute('display_name_fa') ?: $model->getAttribute('name_fa');
        $code = $model->getAttribute('code');

        return [
            'value' => is_string($code) ? $code : (string) $model->getKey(),
            'code' => $code,
            'label' => $label,
            'name_fa' => $model->getAttribute('name_fa'),
        ];
    }
}
