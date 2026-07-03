<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class CityRegionBuilder extends LocationBuilder
{
    public function forCity(mixed $city): static
    {
        if ($city instanceof Model) {
            $this->where('city_id', $city->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($city);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('city_id', (int) $value);

            return $this;
        }

        return $this->forCityCode($value);
    }

    public function forCityCode(string $code): static
    {
        $this->whereHas('city', fn ($query) => $query->where('code', $code));

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

    public function municipal(): static
    {
        return $this->type('municipal_region');
    }

    public function type(?string $type): static
    {
        $type = LocationFilterHelpers::string($type);

        if ($type === null || $type === 'all') {
            return $this;
        }

        $this->where('type', $type);

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

        if (array_key_exists('number', $filters)) {
            $this->number($filters['number']);
        }

        if (($type = LocationFilterHelpers::string($filters['type'] ?? null)) !== null) {
            $this->type($type);
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'number' => 'number',
            'city' => 'city_id',
        ]);
    }
}
