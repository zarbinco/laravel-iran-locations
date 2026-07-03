<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class ProvinceRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return $this->commonLocationRules('province', 'province');
    }
}
