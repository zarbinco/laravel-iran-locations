<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;

class LocationAlias extends Model
{
    use HasConfigurableTable;
    use HasLocationSource;

    protected string $tableConfigKey = 'location_aliases';

    protected $fillable = [
        'location_type',
        'location_id',
        'alias',
        'normalized_alias',
        'reason',
        'source',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $alias): void {
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
}
