<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

use Zarbin\IranLocations\Support\LocationModelResolver;

trait HasConfigurableTable
{
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return LocationModelResolver::table($this->tableConfigKey);
    }
}
