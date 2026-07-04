<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProvinceSelect extends LocationSelect
{
    /**
     * @return Collection<int, Model>
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
