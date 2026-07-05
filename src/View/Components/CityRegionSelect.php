<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Zarbin\IranLocations\Support\LocationRecord;

class CityRegionSelect extends LocationSelect
{
    public function __construct(
        string $name,
        int|string|null $selected = null,
        ?string $placeholder = null,
        bool $disabled = false,
        bool $required = false,
        public int|string|null $cityId = null,
        public ?string $cityCode = null,
        public int|string|null $provinceId = null,
        public ?string $provinceCode = null,
        public int|string|null $countyId = null,
        public ?string $countyCode = null,
        public int|string|null $officialDistrictId = null,
        public ?string $officialDistrictCode = null,
    ) {
        parent::__construct($name, $selected, $placeholder, $disabled, $required);
    }

    /**
     * @return Collection<int, LocationRecord>
     */
    public function options(): Collection
    {
        return $this->optionRecords('city_region', [
            'province_id' => $this->provinceId,
            'province_code' => $this->provinceCode,
            'county_id' => $this->countyId,
            'county_code' => $this->countyCode,
            'official_district_id' => $this->officialDistrictId,
            'official_district_code' => $this->officialDistrictCode,
            'city_id' => $this->cityId,
            'city_code' => $this->cityCode,
        ]);
    }

    public function render(): View
    {
        return $this->componentView('city-region-select');
    }
}
