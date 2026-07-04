<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zarbin\IranLocations\Builders\RuralDistrictBuilder;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;
use Zarbin\IranLocations\Models\Concerns\HasDisplayName;
use Zarbin\IranLocations\Models\Concerns\HasLocationAliases;
use Zarbin\IranLocations\Models\Concerns\HasLocationReplacement;
use Zarbin\IranLocations\Models\Concerns\HasLocationSource;
use Zarbin\IranLocations\Models\Concerns\HasLocationStatus;
use Zarbin\IranLocations\Models\Concerns\HasStableCode;
use Zarbin\IranLocations\Models\Concerns\NormalizesLocationName;
use Zarbin\IranLocations\Support\LocationModelResolver;

class RuralDistrict extends Model
{
    use HasConfigurableTable;
    use HasDisplayName;
    use HasLocationAliases;
    use HasLocationReplacement;
    use HasLocationSource;
    use HasLocationStatus;
    use HasStableCode;
    use NormalizesLocationName;

    protected string $tableConfigKey = 'rural_district';

    protected string $modelConfigKey = 'rural_district';

    protected $fillable = [
        'province_id',
        'county_id',
        'official_district_id',
        'code',
        'name_fa',
        'name_en',
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
        'is_active' => 'boolean',
        'deprecated_at' => 'datetime',
    ];

    public function newEloquentBuilder($query): RuralDistrictBuilder
    {
        return new RuralDistrictBuilder($query);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('province'), 'province_id');
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('county'), 'county_id');
    }

    public function officialDistrict(): BelongsTo
    {
        return $this->belongsTo(LocationModelResolver::model('official_district'), 'official_district_id');
    }
}
