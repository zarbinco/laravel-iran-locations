<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CityIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'is_capital' => ['nullable'],
            'has_regions' => ['nullable'],
            'has_neighborhoods' => ['nullable'],
        ]);
    }
}
