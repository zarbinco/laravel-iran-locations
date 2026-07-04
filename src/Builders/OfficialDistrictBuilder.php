<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class OfficialDistrictBuilder extends LocationBuilder
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

    public function forCounty(mixed $county): static
    {
        if ($county instanceof Model) {
            $this->where('county_id', $county->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($county);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('county_id', (int) $value);

            return $this;
        }

        return $this->forCountyCode($value);
    }

    public function forCountyCode(string $code): static
    {
        $this->whereHas('county', fn ($query) => $query->where('code', $code));

        return $this;
    }

    public function hasCities(): static
    {
        $this->has('cities');

        return $this;
    }

    public function hasRuralDistricts(): static
    {
        $this->has('ruralDistricts');

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

        if (array_key_exists('county_id', $filters)) {
            $this->forCounty($filters['county_id']);
        }

        if (($countyCode = LocationFilterHelpers::string($filters['county_code'] ?? null)) !== null) {
            $this->forCountyCode($countyCode);
        }

        if (($hasCities = LocationFilterHelpers::boolean($filters['has_cities'] ?? null)) !== null) {
            $hasCities ? $this->hasCities() : $this->doesntHave('cities');
        }

        if (($hasRuralDistricts = LocationFilterHelpers::boolean($filters['has_rural_districts'] ?? null)) !== null) {
            $hasRuralDistricts ? $this->hasRuralDistricts() : $this->doesntHave('ruralDistricts');
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'province' => 'province_id',
            'county' => 'county_id',
        ]);
    }
}
