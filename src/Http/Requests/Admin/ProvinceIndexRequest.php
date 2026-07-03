<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class ProvinceIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonIndexRules(), [
            'has_cities' => ['nullable'],
        ]);
    }
}
