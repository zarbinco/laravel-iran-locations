<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CityAreaSelect extends LocationSelect
{
    public function __construct(
        string $name,
        int|string|null $selected = null,
        ?string $placeholder = null,
        bool $disabled = false,
        bool $required = false,
        public int|string|null $cityId = null,
        public ?string $cityCode = null,
        public int|string|null $regionId = null,
        public ?string $regionCode = null,
        public int|string|null $cityRegionId = null,
        public ?string $cityRegionCode = null,
    ) {
        parent::__construct($name, $selected, $placeholder, $disabled, $required);
    }

    /**
     * @return Collection<int, Model>
     */
    public function options(): Collection
    {
        return $this->optionRecords('city_area', [
            'city_id' => $this->cityId,
            'city_code' => $this->cityCode,
            'region_id' => $this->cityRegionId ?? $this->regionId,
            'region_code' => $this->cityRegionCode ?? $this->regionCode,
        ]);
    }

    public function render(): View
    {
        return $this->componentView('city-area-select');
    }
}
