<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Builders;

use Illuminate\Database\Eloquent\Builder;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Filters\LocationFilterHelpers;

class LocationBuilder extends Builder
{
    public function active(): static
    {
        $this->where('is_active', true)->whereNull('deprecated_at');

        return $this;
    }

    public function inactive(): static
    {
        $this->where('is_active', false);

        return $this;
    }

    public function deprecated(): static
    {
        $this->whereNotNull('deprecated_at');

        return $this;
    }

    public function notDeprecated(): static
    {
        $this->whereNull('deprecated_at');

        return $this;
    }

    public function package(): static
    {
        $this->where('source', 'package');

        return $this;
    }

    public function custom(): static
    {
        $this->where('source', 'custom');

        return $this;
    }

    public function source(?string $source): static
    {
        $source = LocationFilterHelpers::string($source);

        if ($source === null || $source === 'all') {
            return $this;
        }

        $this->where('source', $source);

        return $this;
    }

    public function byCode(string $code): static
    {
        $this->where('code', $code);

        return $this;
    }

    public function bySlug(string $slug): static
    {
        $this->where('slug', $slug);

        return $this;
    }

    public function search(?string $term): static
    {
        $term = LocationFilterHelpers::string($term);

        if ($term === null) {
            return $this;
        }

        /** @var LocationNormalizer $normalizer */
        $normalizer = app(LocationNormalizer::class);
        $normalized = $normalizer->search($term);

        if ($normalized === '') {
            return $this;
        }

        $this->where(function (Builder $query) use ($term, $normalized): void {
            $query
                ->where('normalized_name', 'like', $this->like($normalized))
                ->orWhere('name_fa', 'like', $this->like($term))
                ->orWhere('slug', 'like', $this->like($normalized))
                ->orWhere('code', 'like', $this->like($term));

            if ((bool) config('iran-locations.search.include_aliases', true) && method_exists($this->getModel(), 'aliases')) {
                $query->orWhereHas('aliases', function (Builder $aliasQuery) use ($normalized): void {
                    $aliasQuery->where('normalized_alias', 'like', $this->like($normalized));
                });
            }
        });

        return $this;
    }

    public function ordered(string $direction = 'asc'): static
    {
        $this
            ->orderBy('normalized_name', $this->direction($direction))
            ->orderBy($this->getModel()->getQualifiedKeyName());

        return $this;
    }

    public function latestUpdated(): static
    {
        $this->orderByDesc($this->qualifyColumn('updated_at'));

        return $this;
    }

    public function filter(array $filters): static
    {
        $this->applyCommonFilters($filters);

        return $this;
    }

    public function applySort(?string $sort): static
    {
        $sort = LocationFilterHelpers::string($sort);

        if ($sort === null) {
            return $this->ordered();
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $key = ltrim($sort, '-');
        $column = $this->sortColumns()[$key] ?? null;

        if ($column === null) {
            return $this->ordered();
        }

        $this->orderBy($this->qualifyColumn($column), $direction)
            ->orderBy($this->getModel()->getQualifiedKeyName());

        return $this;
    }

    protected function applyCommonFilters(array $filters): void
    {
        if (($q = LocationFilterHelpers::string($filters['q'] ?? null)) !== null) {
            $this->search($q);
        }

        if (($status = LocationFilterHelpers::string($filters['status'] ?? null)) !== null) {
            $this->applyStatus($status);
        }

        if (array_key_exists('source', $filters)) {
            $this->source(LocationFilterHelpers::string($filters['source']));
        }

        if (($code = LocationFilterHelpers::string($filters['code'] ?? null)) !== null) {
            $this->byCode($code);
        }

        if (($slug = LocationFilterHelpers::string($filters['slug'] ?? null)) !== null) {
            $this->bySlug($slug);
        }

        if (array_key_exists('sort', $filters)) {
            $this->applySort(LocationFilterHelpers::string($filters['sort']));
        }
    }

    protected function applyStatus(string $status): void
    {
        match ($status) {
            'active' => $this->active(),
            'inactive' => $this->inactive(),
            'deprecated' => $this->deprecated(),
            'all' => $this,
            default => $this,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        return [
            'name' => 'normalized_name',
            'code' => 'code',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    protected function direction(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }

    protected function like(string $value): string
    {
        return '%'.$value.'%';
    }
}
