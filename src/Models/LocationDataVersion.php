<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;

class LocationDataVersion extends Model
{
    use HasConfigurableTable;

    protected string $tableConfigKey = 'data_version';

    protected $fillable = [
        'data_version',
        'package_version',
        'checksum',
        'summary',
        'applied_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'applied_at' => 'datetime',
    ];

    public function scopeApplied(Builder $query): Builder
    {
        return $query->whereNotNull('applied_at');
    }

    public static function latestApplied(): ?self
    {
        /** @var self|null $version */
        $version = self::query()
            ->applied()
            ->orderByDesc('applied_at')
            ->orderByDesc((new self)->getQualifiedKeyName())
            ->first();

        return $version;
    }

    public static function hasApplied(string $dataVersion): bool
    {
        return self::query()
            ->applied()
            ->where('data_version', $dataVersion)
            ->exists();
    }

    public static function latestAppliedVersion(): ?string
    {
        return self::latestApplied()?->getAttribute('data_version');
    }
}
