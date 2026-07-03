<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class CityBuilder extends LocationBuilder
{
    public function forProvince(mixed $province): static
    {
        if ($province instanceof Model) {
            $this->where('province_id', $province->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($province);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('province_id', (int) $value);

            return $this;
        }

        return $this->forProvinceCode($value);
    }

    public function forProvinceCode(string $code): static
    {
        $this->whereHas('province', fn ($query) => $query->where('code', $code));

        return $this;
    }

    public function capital(): static
    {
        $this->where('is_province_capital', true);

        return $this;
    }

    public function notCapital(): static
    {
        $this->where('is_province_capital', false);

        return $this;
    }

    public function hasRegions(): static
    {
        $this->has('regions');

        return $this;
    }

    public function hasNeighborhoods(): static
    {
        $this->has('neighborhoods');

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        if (array_key_exists('province_id', $filters)) {
            $this->forProvince($filters['province_id']);
        }

        if (($code = LocationFilterHelpers::string($filters['province_code'] ?? null)) !== null) {
            $this->forProvinceCode($code);
        }

        if (($capital = LocationFilterHelpers::boolean($filters['is_capital'] ?? null)) !== null) {
            $capital ? $this->capital() : $this->notCapital();
        }

        if (($hasRegions = LocationFilterHelpers::boolean($filters['has_regions'] ?? null)) !== null) {
            $hasRegions ? $this->hasRegions() : $this->doesntHave('regions');
        }

        if (($hasNeighborhoods = LocationFilterHelpers::boolean($filters['has_neighborhoods'] ?? null)) !== null) {
            $hasNeighborhoods ? $this->hasNeighborhoods() : $this->doesntHave('neighborhoods');
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'province' => 'province_id',
        ]);
    }
}
