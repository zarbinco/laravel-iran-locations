<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OfficialDistrictSelect extends LocationSelect
{
    public function __construct(
        string $name,
        int|string|null $selected = null,
        ?string $placeholder = null,
        bool $disabled = false,
        bool $required = false,
        public int|string|null $provinceId = null,
        public ?string $provinceCode = null,
        public int|string|null $countyId = null,
        public ?string $countyCode = null,
    ) {
        parent::__construct($name, $selected, $placeholder, $disabled, $required);
    }

    /**
     * @return Collection<int, Model>
     */
    public function options(): Collection
    {
        return $this->optionRecords('official_district', [
            'province_id' => $this->provinceId,
            'province_code' => $this->provinceCode,
            'county_id' => $this->countyId,
            'county_code' => $this->countyCode,
        ]);
    }

    public function render(): View
    {
        return $this->componentView('official-district-select');
    }
}
