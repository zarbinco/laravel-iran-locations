<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
    ) {
        parent::__construct($name, $selected, $placeholder, $disabled, $required);
    }

    /**
     * @return Collection<int, Model>
     */
    public function options(): Collection
    {
        return $this->optionRecords('city_region', [
            'city_id' => $this->cityId,
            'city_code' => $this->cityCode,
        ]);
    }

    public function render(): View
    {
        return $this->componentView('city-region-select');
    }
}
