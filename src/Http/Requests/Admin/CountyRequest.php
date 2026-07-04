<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

class CountyRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge($this->commonLocationRules('county', 'county'), [
            'province_id' => ['required', 'integer', $this->existsIn('province')],
        ]);
    }
}
