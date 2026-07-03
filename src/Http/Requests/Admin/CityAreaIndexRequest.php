<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CityAreaIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'city_id' => ['nullable', 'integer'],
            'city_code' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
