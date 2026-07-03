<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class ProvinceBuilder extends LocationBuilder
{
    public function hasCities(): static
    {
        $this->has('cities');

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        if (($hasCities = LocationFilterHelpers::boolean($filters['has_cities'] ?? null)) !== null) {
            $hasCities ? $this->hasCities() : $this->doesntHave('cities');
        }

        return $this;
    }
}
