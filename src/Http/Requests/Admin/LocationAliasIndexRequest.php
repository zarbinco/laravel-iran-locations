<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAliasIndexRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:'.$this->searchMinLength(), 'max:255'],
            'source' => ['nullable', 'in:package,custom,all'],
            'location_type' => ['nullable', Rule::in(LocationModelResolver::locationTypeKeys())],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
