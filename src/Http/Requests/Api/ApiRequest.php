<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function commonRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive,deprecated,all'],
            'source' => ['nullable', 'in:package,custom,all'],
            'code' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage()],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', config('iran-locations.api.pagination.per_page', config('iran-locations.pagination.per_page', 25)));

        return max(1, min($perPage, $this->maxPerPage()));
    }

    public function limit(): int
    {
        return $this->perPage();
    }

    protected function maxPerPage(): int
    {
        return (int) config('iran-locations.api.pagination.max_per_page', config('iran-locations.pagination.max_per_page', 100));
    }
}
