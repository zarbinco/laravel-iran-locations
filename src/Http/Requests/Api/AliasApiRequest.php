<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Zarbin\IranLocations\Support\LocationModelResolver;

class AliasApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:'.$this->searchMinLength(), 'max:100'],
            'source' => ['nullable', 'in:package,custom,all'],
            'location_type' => ['nullable', Rule::in(LocationModelResolver::locationTypeKeys())],
            'status' => ['nullable', 'in:active,inactive,deprecated,all'],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
