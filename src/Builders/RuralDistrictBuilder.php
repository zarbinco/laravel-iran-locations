<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Model;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class RuralDistrictBuilder extends LocationBuilder
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

    public function forOfficialDistrict(mixed $district): static
    {
        if ($district instanceof Model) {
            $this->where('official_district_id', $district->getKey());

            return $this;
        }

        $value = LocationFilterHelpers::string($district);

        if ($value === null) {
            return $this;
        }

        if (is_numeric($value)) {
            $this->where('official_district_id', (int) $value);

            return $this;
        }

        return $this->forOfficialDistrictCode($value);
    }

    public function forOfficialDistrictCode(string $code): static
    {
        $this->whereHas('officialDistrict', fn ($query) => $query->where('code', $code));

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

        if (($districtCode = LocationFilterHelpers::string($filters['official_district_code'] ?? null)) !== null) {
            $this->forOfficialDistrictCode($districtCode);
        }

        return $this;
    }

    protected function sortColumns(): array
    {
        return array_merge(parent::sortColumns(), [
            'province' => 'province_id',
            'county' => 'county_id',
            'official_district' => 'official_district_id',
        ]);
    }
}
