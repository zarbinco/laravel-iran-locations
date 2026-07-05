<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Zarbin\IranLocations\Support\LocationRecord;

class ProvinceSelect extends LocationSelect
{
    /**
     * @return Collection<int, LocationRecord>
     */
    public function options(): Collection
    {
        return $this->optionRecords('province');
    }

    public function render(): View
    {
        return $this->componentView('province-select');
    }
}
