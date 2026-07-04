<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class SearchApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
        ];
    }

    public function limit(): int
    {
        $limit = (int) $this->input('limit', config('iran-locations.search.limit', 10));

        return max(1, min($limit, $this->maxPerPage()));
    }
}
