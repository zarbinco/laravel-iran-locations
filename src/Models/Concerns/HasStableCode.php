<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasStableCode
{
    public function getRouteKeyName(): string
    {
        $routeKey = config('iran-locations.route_key', 'id');

        return is_string($routeKey) && in_array($routeKey, ['id', 'code', 'slug'], true)
            ? $routeKey
            : 'id';
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
