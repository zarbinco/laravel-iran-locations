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

    public function forProvince(mixed $province): static
    {
        if ($province instanceof Model) {
            $this->whereHas('city', fn ($query) => $query->where('province_id', $province->getKey()));

            return $this;
        }

        $value = LocationFilterHelpers::string($province);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->whereHas('city', fn ($query) => $query->where('province_id', (int) $value));

            return $this;
        }

        return $this->forProvinceCode($value);
    }

    public function forProvinceCode(string $code): static
    {
        $this->whereHas('city.province', fn ($query) => $query->where('code', $code));

        return $this;
    }

    public function forCounty(mixed $county): static
    {
        if ($county instanceof Model) {
            $this->whereHas('city', fn ($query) => $query->where('county_id', $county->getKey()));

            return $this;
        }

        $value = LocationFilterHelpers::string($county);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->whereHas('city', fn ($query) => $query->where('county_id', (int) $value));

            return $this;
        }

        return $this->forCountyCode($value);
    }

    public function forCountyCode(string $code): static
    {
        $this->whereHas('city.county', fn ($query) => $query->where('code', $code));

        return $this;
    }

    public function forOfficialDistrict(mixed $officialDistrict): static
    {
        if ($officialDistrict instanceof Model) {
            $this->whereHas('city', fn ($query) => $query->where('official_district_id', $officialDistrict->getKey()));

            return $this;
        }

        $value = LocationFilterHelpers::string($officialDistrict);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->whereHas('city', fn ($query) => $query->where('official_district_id', (int) $value));

            return $this;
        }

        return $this->forOfficialDistrictCode($value);
    }

    public function forOfficialDistrictCode(string $code): static
    {
        $this->whereHas('city.officialDistrict', fn ($query) => $query->where('code', $code));

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

    public function hasNeighborhoods(): static
    {
        $this->has('neighborhoods');

        return $this;
    }

    public function missingNeighborhoods(): static
    {
        $this->doesntHave('neighborhoods');

        return $this;
    }

    public function hasAreas(): static
    {
        $this->has('areas');

        return $this;
    }

    public function missingAreas(): static
    {
        $this->doesntHave('areas');

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

        if (array_key_exists('official_district_id', $filters)) {
            $this->forOfficialDistrict($filters['official_district_id']);
        }

        if (($officialDistrictCode = LocationFilterHelpers::string($filters['official_district_code'] ?? null)) !== null) {
            $this->forOfficialDistrictCode($officialDistrictCode);
        }

        if (array_key_exists('number', $filters)) {
            $this->number($filters['number']);
        }

        if (($type = LocationFilterHelpers::string($filters['type'] ?? null)) !== null) {
            $this->type($type);
        }

        if (($hasNeighborhoods = LocationFilterHelpers::boolean($filters['has_neighborhoods'] ?? null)) !== null) {
            $hasNeighborhoods ? $this->hasNeighborhoods() : $this->missingNeighborhoods();
        }

        if (($hasAreas = LocationFilterHelpers::boolean($filters['has_areas'] ?? null)) !== null) {
            $hasAreas ? $this->hasAreas() : $this->missingAreas();
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'number' => 'number',
            'city' => 'city_id',
            'province' => 'city_id',
            'county' => 'city_id',
            'official_district' => 'city_id',
        ]);
    }
}
