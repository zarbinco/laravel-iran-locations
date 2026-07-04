<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class CountyBuilder extends LocationBuilder
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

    public function hasCities(): static
    {
        $this->has('cities');

        return $this;
    }

    public function hasOfficialDistricts(): static
    {
        $this->has('officialDistricts');

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        if (array_key_exists('province_id', $filters)) {
            $this->forProvince($filters['province_id']);
        }

        if (($provinceCode = LocationFilterHelpers::string($filters['province_code'] ?? null)) !== null) {
            $this->forProvinceCode($provinceCode);
        }

        if (($hasCities = LocationFilterHelpers::boolean($filters['has_cities'] ?? null)) !== null) {
            $hasCities ? $this->hasCities() : $this->doesntHave('cities');
        }

        if (($hasOfficialDistricts = LocationFilterHelpers::boolean($filters['has_official_districts'] ?? null)) !== null) {
            $hasOfficialDistricts ? $this->hasOfficialDistricts() : $this->doesntHave('officialDistricts');
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
