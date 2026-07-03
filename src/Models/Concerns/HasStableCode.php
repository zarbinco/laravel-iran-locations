<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasStableCode
{
    public function getRouteKeyName(): string
    {
        return (string) config('iran-locations.route_key', 'id');
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
