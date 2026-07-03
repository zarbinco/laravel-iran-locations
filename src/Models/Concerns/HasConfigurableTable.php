<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

trait HasConfigurableTable
{
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return (string) config("iran-locations.tables.{$this->tableConfigKey}", parent::getTable());
    }
}
