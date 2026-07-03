<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Zarbin\IranLocations\Builders\NeighborhoodBuilder;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasDisplayName;
use Zarbin\IranLocations\Models\Concerns\HasLocationAliases;
use Zarbin\IranLocations\Models\Concerns\HasLocationReplacement;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;
use Zarbin\IranLocations\Models\Concerns\HasLocationStatus;
use Zarbin\IranLocations\Models\Concerns\HasStableCode;
use Zarbin\IranLocations\Models\Concerns\NormalizesLocationName;
use Zarbin\IranLocations\Support\LocationModelResolver;

class Neighborhood extends Model
{
    use HasConfigurableTable;
    use HasDisplayName;
    use HasLocationAliases;
    use HasLocationReplacement;
    use HasLocationSource;
    use HasLocationStatus;
    use HasStableCode;
    use NormalizesLocationName;

    protected string $tableConfigKey = 'neighborhood';

    protected string $modelConfigKey = 'neighborhood';

    protected $fillable = [
        'city_id',
        'default_city_region_id',
        'default_city_area_id',
        'code',
        'name_fa',
        'name_en',
        'slug',
        'normalized_name',
        'type',
        'display_name_fa',
        'latitude',
        'longitude',
        'is_active',
        'source',
        'source_version',
        'data_version',
        'deprecated_at',
        'replaced_by_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime',
    ];

    public function newEloquentBuilder($query): NeighborhoodBuilder
    {
        return new NeighborhoodBuilder($query);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('city'), 'city_id');
    }

    public function defaultRegion(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('city_region'), 'default_city_region_id');
    }

    public function defaultArea(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('city_area'), 'default_city_area_id');
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(
            LocationModelResolver::model('city_region'),
            LocationModelResolver::table('neighborhood_region'),
            'neighborhood_id',
            'city_region_id',
        )->withPivot(['is_primary', 'source', 'confidence'])->withTimestamps();
    }
}
