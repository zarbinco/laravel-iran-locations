<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Models\Concerns\HasConfigurableTable;

class LocationDataVersion extends Model
{
    use HasConfigurableTable;

    protected string $tableConfigKey = 'data_versions';

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
}
