<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
     * @return array<int, mixed>
     */
    protected function requiredIdRules(string $tableKey): array
    {
        return ['required', 'integer', 'min:1', $this->existsIn($tableKey)];
    }

    /**
     * @return array<int, mixed>
     */
    protected function nullableIdRules(string $tableKey): array
    {
        return ['nullable', 'integer', 'min:1', $this->existsIn($tableKey)];
    }

    protected function modelExistsById(string $modelKey, int|string|null $id): bool
    {
        if (blank($id)) {
            return false;
        }

        $class = LocationModelResolver::model($modelKey);

        return $class::query()->whereKey($id)->exists();
    }

    protected function modelColumnValue(string $modelKey, int|string|null $id, string $column): mixed
    {
        if (blank($id)) {
            return null;
        }

        $class = LocationModelResolver::model($modelKey);
        $model = $class::query()->whereKey($id)->first();

        return $model?->getAttribute($column);
    }

    protected function parentMatches(string $modelKey, int|string|null $id, string $column, int|string|null $expected): bool
    {
        if (blank($id) || blank($expected)) {
            return true;
        }

        $actual = $this->modelColumnValue($modelKey, $id, $column);

        return $actual !== null && (string) $actual === (string) $expected;
    }

    protected function addHierarchyError(Validator $validator, string $field, string $message): void
    {
        $validator->errors()->add($field, $message);
    }

    /**
     * @return array<string, mixed>
     */
    protected function commonIndexRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:'.$this->searchMinLength(), 'max:100'],
            'status' => ['nullable', 'in:active,inactive,deprecated,all'],
            'source' => ['nullable', 'in:package,custom,all'],
            'code' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
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
            'source' => $this->sourceRule(),
            'source_version' => ['nullable', 'string', 'max:255'],
            'data_version' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function sourceRule(): array
    {
        $allowed = (bool) config('iran-locations.data.allow_package_record_direct_edit', false)
            ? ['package', 'custom']
            : ['custom'];

        return ['nullable', Rule::in($allowed)];
    }

    protected function searchMinLength(): int
    {
        $value = config('iran-locations.search.min_length', 2);

        if (! is_numeric($value)) {
            return 2;
        }

        return max(1, (int) $value);
    }
}
