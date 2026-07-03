<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocationStatus
{
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active') && ! $this->isDeprecated();
    }

    public function isInactive(): bool
    {
        return ! (bool) $this->getAttribute('is_active');
    }

    public function isDeprecated(): bool
    {
        return $this->getAttribute('deprecated_at') !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('deprecated_at');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeDeprecated(Builder $query): Builder
    {
        return $query->whereNotNull('deprecated_at');
    }
}
