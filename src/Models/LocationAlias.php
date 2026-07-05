<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;
use Zarbin\IranLocations\Models\Concerns\HasLocationStatus;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAlias extends Model
{
    use HasConfigurableTable;
    use HasLocationSource;
    use HasLocationStatus;

    protected string $tableConfigKey = 'location_alias';

    protected $fillable = [
        'location_type',
        'location_id',
        'alias',
        'normalized_alias',
        'reason',
        'is_active',
        'source',
        'source_version',
        'data_version',
        'deprecated_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'source' => self::SOURCE_PACKAGE,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $alias): void {
            $type = $alias->getAttribute('location_type');

            if (is_string($type) && $type !== '') {
                $alias->setAttribute('location_type', LocationModelResolver::normalizeLocationType($type));
            }

            if (! (bool) config('iran-locations.normalization.aliases', true)) {
                return;
            }

            $value = $alias->getAttribute('alias');

            if (! is_string($value) || blank($value)) {
                return;
            }

            if ($alias->isDirty('alias') || blank($alias->getAttribute('normalized_alias'))) {
                $alias->setAttribute('normalized_alias', app(LocationNormalizer::class)->search($value));
            }
        });
    }

    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function markDeprecated(?Model $replacement = null): static
    {
        $this->setAttribute('is_active', false);
        $this->setAttribute('deprecated_at', $this->freshTimestamp());

        return $this;
    }

    public function restoreFromDeprecation(): static
    {
        $this->setAttribute('is_active', true);
        $this->setAttribute('deprecated_at', null);

        return $this;
    }
}
