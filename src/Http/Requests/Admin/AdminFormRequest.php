<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Zarbin\IranLocations\Support\LocationModelResolver;

abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function table(string $key): string
    {
        return LocationModelResolver::table($key);
    }

    protected function uniqueCode(string $tableKey, string $routeParameter): mixed
    {
        return Rule::unique($this->table($tableKey), 'code')->ignore($this->route($routeParameter));
    }

    protected function existsIn(string $tableKey): mixed
    {
        return Rule::exists($this->table($tableKey), 'id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function commonIndexRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,deprecated,all'],
            'source' => ['nullable', 'in:package,custom,all'],
            'code' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function commonLocationRules(string $tableKey, string $routeParameter): array
    {
        return [
            'code' => ['required', 'string', 'max:255', $this->uniqueCode($tableKey, $routeParameter)],
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'display_name_fa' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'source' => ['nullable', 'in:package,custom'],
            'source_version' => ['nullable', 'string', 'max:255'],
            'data_version' => ['nullable', 'string', 'max:255'],
        ];
    }
}
