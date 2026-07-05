<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CountyIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'province_id' => ['nullable', 'integer', 'min:1'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'has_cities' => ['nullable'],
            'has_official_districts' => ['nullable'],
        ]);
    }
}
