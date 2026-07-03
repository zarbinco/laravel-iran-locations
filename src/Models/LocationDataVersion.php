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
}
