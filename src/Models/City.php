<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasDisplayName;
use Zarbin\IranLocations\Models\Concerns\HasLocationAliases;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;
use Zarbin\IranLocations\Models\Concerns\HasLocationStatus;
use Zarbin\IranLocations\Models\Concerns\HasStableCode;
use Zarbin\IranLocations\Models\Concerns\NormalizesLocationName;

class City extends Model
{
    use HasConfigurableTable;
    use HasDisplayName;
    use HasLocationAliases;
    use HasLocationSource;
    use HasLocationStatus;
    use HasStableCode;
    use NormalizesLocationName;

    protected string $tableConfigKey = 'cities';

    protected $fillable = [
        'province_id',
        'code',
        'name_fa',
        'name_en',
        'slug',
        'normalized_name',
        'display_name_fa',
        'is_province_capital',
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
        'is_province_capital' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(config('iran-locations.models.province'), 'province_id');
    }

    public function regions(): HasMany
    {
        return $this->hasMany(config('iran-locations.models.city_region'), 'city_id');
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(config('iran-locations.models.neighborhood'), 'city_id');
    }
}
