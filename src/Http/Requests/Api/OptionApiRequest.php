<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

class OptionApiRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer'],
            'province_code' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer'],
            'city_code' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'integer'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:neighborhood,street,boulevard,square,highway,park,area,unknown,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
        ];
    }

    public function limit(): int
    {
        $limit = (int) $this->input('limit', $this->input('per_page', $this->maxPerPage()));

        return max(1, min($limit, $this->maxPerPage()));
    }
}
