<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Models\Concerns;

trait HasDisplayName
{
    public function displayName(): string
    {
        $displayName = $this->getAttribute('display_name_fa');

        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        return (string) $this->getAttribute('name_fa');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->displayName();
    }
}
