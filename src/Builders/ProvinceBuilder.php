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

    public function hasCounties(): static
    {
        $this->has('counties');

        return $this;
    }

    public function hasOfficialDistricts(): static
    {
        $this->has('officialDistricts');

        return $this;
    }

    public function hasRuralDistricts(): static
    {
        $this->has('ruralDistricts');

        return $this;
    }

    public function hasRegions(): static
    {
        $this->whereHas('cities.regions');

        return $this;
    }

    public function hasNeighborhoods(): static
    {
        $this->whereHas('cities.neighborhoods');

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        if (($hasCities = LocationFilterHelpers::boolean($filters['has_cities'] ?? null)) !== null) {
            $hasCities ? $this->hasCities() : $this->doesntHave('cities');
        }

        if (($hasCounties = LocationFilterHelpers::boolean($filters['has_counties'] ?? null)) !== null) {
            $hasCounties ? $this->hasCounties() : $this->doesntHave('counties');
        }

        if (($hasOfficialDistricts = LocationFilterHelpers::boolean($filters['has_official_districts'] ?? null)) !== null) {
            $hasOfficialDistricts ? $this->hasOfficialDistricts() : $this->doesntHave('officialDistricts');
        }

        if (($hasRuralDistricts = LocationFilterHelpers::boolean($filters['has_rural_districts'] ?? null)) !== null) {
            $hasRuralDistricts ? $this->hasRuralDistricts() : $this->doesntHave('ruralDistricts');
        }

        if (($hasRegions = LocationFilterHelpers::boolean($filters['has_regions'] ?? null)) !== null) {
            $hasRegions ? $this->hasRegions() : $this->whereDoesntHave('cities.regions');
        }

        if (($hasNeighborhoods = LocationFilterHelpers::boolean($filters['has_neighborhoods'] ?? null)) !== null) {
            $hasNeighborhoods ? $this->hasNeighborhoods() : $this->whereDoesntHave('cities.neighborhoods');
        }

        return $this;
    }
}
