<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Zarbin\IranLocations\Support\LocationModelResolver;

trait HasLocationAliases
{
    public function aliases(): MorphMany
    {
        return $this->morphMany(LocationModelResolver::model('location_alias'), 'location');
    }
}
