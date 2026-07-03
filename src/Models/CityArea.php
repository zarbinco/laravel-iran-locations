<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

class CityArea extends Model
{
    use HasConfigurableTable;
    use HasDisplayName;
    use HasLocationAliases;
    use HasLocationReplacement;
    use HasLocationSource;
    use HasLocationStatus;
    use HasStableCode;
    use NormalizesLocationName;

    protected string $tableConfigKey = 'city_area';

    protected string $modelConfigKey = 'city_area';

    protected $fillable = [
        'city_region_id',
        'code',
        'number',
        'name_fa',
        'slug',
        'normalized_name',
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

    public function region(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('city_region'), 'city_region_id');
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(LocationModelResolver::model('neighborhood'), 'default_city_area_id');
    }
}
