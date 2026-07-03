<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasDisplayName;
use Zarbin\IranLocations\Models\Concerns\HasLocationAliases;
use Zarbin\IranLocations\Models\Concerns\HasLocationReplacement;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;
use Zarbin\IranLocations\Models\Concerns\HasLocationStatus;
use Zarbin\IranLocations\Models\Concerns\HasStableCode;
use Zarbin\IranLocations\Models\Concerns\NormalizesLocationName;
use Zarbin\IranLocations\Support\LocationModelResolver;

class CityRegion extends Model
{
    use HasConfigurableTable;
    use HasDisplayName;
    use HasLocationAliases;
    use HasLocationReplacement;
    use HasLocationSource;
    use HasLocationStatus;
    use HasStableCode;
    use NormalizesLocationName;

    protected string $tableConfigKey = 'city_region';

    protected string $modelConfigKey = 'city_region';

    protected $fillable = [
        'city_id',
        'code',
        'number',
        'name_fa',
        'name_en',
        'slug',
        'normalized_name',
        'type',
        'display_name_fa',
        'is_active',
        'source',
        'source_version',
        'data_version',
        'deprecated_at',
        'replaced_by_id',
    ];

    protected $casts = [
        'number' => 'integer',
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('city'), 'city_id');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(LocationModelResolver::model('city_area'), 'city_region_id');
    }

    public function neighborhoods(): BelongsToMany
    {
        return $this->belongsToMany(
            LocationModelResolver::model('neighborhood'),
            LocationModelResolver::table('neighborhood_region'),
            'city_region_id',
            'neighborhood_id',
        )->withPivot(['is_primary', 'source', 'confidence'])->withTimestamps();
    }
}
