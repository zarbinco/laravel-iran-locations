<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
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
            'display_name_fa' => $this->displayName($model),
            'name_en' => $model->getAttribute('name_en'),
            'slug' => $model->getAttribute('slug'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'source' => $model->getAttribute('source'),
            'data_version' => $model->getAttribute('data_version'),
        ];
    }

    private function displayName(Model $model): ?string
    {
        $displayName = $model->getAttribute('display_name_fa') ?: $model->getAttribute('name_fa');

        return is_string($displayName) ? $displayName : null;
    }
}
