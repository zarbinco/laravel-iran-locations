<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocationSource
{
    public const SOURCE_PACKAGE = 'package';

    public const SOURCE_CUSTOM = 'custom';

    public function isPackageRecord(): bool
    {
        return $this->getAttribute('source') === self::SOURCE_PACKAGE;
    }

    public function isCustomRecord(): bool
    {
        return $this->getAttribute('source') === self::SOURCE_CUSTOM;
    }

    public function scopePackage(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_PACKAGE);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_CUSTOM);
    }
}
