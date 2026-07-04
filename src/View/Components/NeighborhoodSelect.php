<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NeighborhoodSelect extends LocationSelect
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
        public int|string|null $areaId = null,
        public ?string $areaCode = null,
        public int|string|null $cityAreaId = null,
        public ?string $cityAreaCode = null,
        public ?string $type = null,
    ) {
        parent::__construct($name, $selected, $placeholder, $disabled, $required);
    }

    /**
     * @return Collection<int, Model>
     */
    public function options(): Collection
    {
        return $this->optionRecords('neighborhood', [
            'city_id' => $this->cityId,
            'city_code' => $this->cityCode,
            'region_id' => $this->cityRegionId ?? $this->regionId,
            'region_code' => $this->cityRegionCode ?? $this->regionCode,
            'area_id' => $this->cityAreaId ?? $this->areaId,
            'area_code' => $this->cityAreaCode ?? $this->areaCode,
            'type' => $this->type,
        ]);
    }

    public function render(): View
    {
        return $this->componentView('neighborhood-select');
    }
}
