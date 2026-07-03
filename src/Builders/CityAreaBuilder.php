<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class CityAreaBuilder extends LocationBuilder
{
    public function forRegion(mixed $region): static
    {
        if ($region instanceof Model) {
            $this->where('city_region_id', $region->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($region);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('city_region_id', (int) $value);

            return $this;
        }

        $this->whereHas('region', fn ($query) => $query->where('code', $value));

        return $this;
    }

    public function forCity(mixed $city): static
    {
        if ($city instanceof Model) {
            $this->whereHas('region', fn ($query) => $query->where('city_id', $city->getKey()));

            return $this;
        }

        $value = LocationFilterHelpers::string($city);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->whereHas('region', fn ($query) => $query->where('city_id', (int) $value));

            return $this;
        }

        return $this->forCityCode($value);
    }

    public function forCityCode(string $code): static
    {
        $this->whereHas('region.city', fn ($query) => $query->where('code', $code));

        return $this;
    }

    public function number(int|string|null $number): static
    {
        $number = LocationFilterHelpers::string($number);

        if ($number === null || ! is_numeric($number)) {
            return $this;
        }

        $this->where('number', (int) $number);

        return $this;
    }

    public function orderedByNumber(): static
    {
        $this->orderBy('number')->orderBy($this->getModel()->getQualifiedKeyName());

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        if (array_key_exists('city_id', $filters)) {
            $this->forCity($filters['city_id']);
        }

        if (($cityCode = LocationFilterHelpers::string($filters['city_code'] ?? null)) !== null) {
            $this->forCityCode($cityCode);
        }

        if (array_key_exists('region_id', $filters)) {
            $this->forRegion($filters['region_id']);
        }

        if (($regionCode = LocationFilterHelpers::string($filters['region_code'] ?? null)) !== null) {
            $this->forRegion($regionCode);
        }

        if (array_key_exists('number', $filters)) {
            $this->number($filters['number']);
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'number' => 'number',
            'region' => 'city_region_id',
        ]);
    }
}
