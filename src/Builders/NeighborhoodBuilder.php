<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class NeighborhoodBuilder extends LocationBuilder
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

    public function forRegion(mixed $region): static
    {
        if ($region instanceof Model) {
            return $this->forRegionId((int) $region->getKey());
        }

        $value = LocationFilterHelpers::string($region);

        if ($value === null) {
            return $this;
        }

        return is_numeric($value)
            ? $this->forRegionId((int) $value)
            : $this->forRegionCode($value);
    }

    public function forRegionCode(string $code): static
    {
        $this->where(function ($query) use ($code): void {
            $query
                ->whereHas('defaultRegion', fn ($regionQuery) => $regionQuery->where('code', $code))
                ->orWhereHas('regions', fn ($regionQuery) => $regionQuery->where('code', $code));
        });

        return $this;
    }

    public function forArea(mixed $area): static
    {
        if ($area instanceof Model) {
            $this->where('default_city_area_id', $area->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($area);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('default_city_area_id', (int) $value);

            return $this;
        }

        $this->forAreaCode($value);

        return $this;
    }

    public function forAreaCode(string $code): static
    {
        $this->whereHas('defaultArea', fn ($query) => $query->where('code', $code));

        return $this;
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

    public function neighborhoods(): static
    {
        return $this->type('neighborhood');
    }

    public function streets(): static
    {
        return $this->type('street');
    }

    public function boulevards(): static
    {
        return $this->type('boulevard');
    }

    public function squares(): static
    {
        return $this->type('square');
    }

    public function highways(): static
    {
        return $this->type('highway');
    }

    public function parks(): static
    {
        return $this->type('park');
    }

    public function areas(): static
    {
        return $this->type('area');
    }

    public function hasRegion(): static
    {
        $this->where(function ($query): void {
            $query->whereNotNull('default_city_region_id')->orWhereHas('regions');
        });

        return $this;
    }

    public function missingRegion(): static
    {
        $this->whereNull('default_city_region_id')->doesntHave('regions');

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

        if (array_key_exists('official_district_id', $filters)) {
            $this->forOfficialDistrict($filters['official_district_id']);
        }

        if (($officialDistrictCode = LocationFilterHelpers::string($filters['official_district_code'] ?? null)) !== null) {
            $this->forOfficialDistrictCode($officialDistrictCode);
        }

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
            $this->forRegionCode($regionCode);
        }

        if (array_key_exists('area_id', $filters)) {
            $this->forArea($filters['area_id']);
        }

        if (($areaCode = LocationFilterHelpers::string($filters['area_code'] ?? null)) !== null) {
            $this->forAreaCode($areaCode);
        }

        if (($type = LocationFilterHelpers::string($filters['type'] ?? null)) !== null) {
            $this->type($type);
        }

        if (($hasRegion = LocationFilterHelpers::boolean($filters['has_region'] ?? null)) !== null) {
            $hasRegion ? $this->hasRegion() : $this->missingRegion();
        }

        if (($missingRegion = LocationFilterHelpers::boolean($filters['missing_region'] ?? null)) !== null) {
            $missingRegion ? $this->missingRegion() : $this->hasRegion();
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'city' => 'city_id',
            'region' => 'default_city_region_id',
            'type' => 'type',
        ]);
    }

    private function forRegionId(int $id): static
    {
        $this->where(function ($query) use ($id): void {
            $query
                ->where('default_city_region_id', $id)
                ->orWhereHas('regions', fn ($regionQuery) => $regionQuery->whereKey($id));
        });

        return $this;
    }
}
