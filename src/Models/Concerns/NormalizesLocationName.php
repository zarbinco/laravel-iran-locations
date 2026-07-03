<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Contracts\LocationNormalizer;

trait NormalizesLocationName
{
    protected static function bootNormalizesLocationName(): void
    {
        static::saving(static function (Model $model): void {
            if (! (bool) config('iran-locations.normalization.on_save', true)) {
                return;
            }

            $name = $model->getAttribute('name_fa');

            if (! is_string($name) || $name === '') {
                return;
            }

            /** @var LocationNormalizer $normalizer */
            $normalizer = app(LocationNormalizer::class);

            if ($model->isDirty('name_fa') || blank($model->getAttribute('normalized_name'))) {
                $model->setAttribute('normalized_name', $normalizer->search($name));
            }

            if (
                (bool) config('iran-locations.normalization.slugs', true)
                && ($model->isDirty('name_fa') || blank($model->getAttribute('slug')))
            ) {
                $model->setAttribute('slug', $normalizer->slug($name));
            }
        });
    }
}
