<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLocationAliases
{
    public function aliases(): MorphMany
    {
        return $this->morphMany(config('iran-locations.models.location_alias'), 'location');
    }
}
