<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Component;
use Zarbin\IranLocations\Builders\LocationBuilder;
use Zarbin\IranLocations\Support\LocationModelResolver;

abstract class LocationSelect extends Component
{
    public function __construct(
        public string $name,
        public int|string|null $selected = null,
        public ?string $placeholder = null,
        public bool $disabled = false,
        public bool $required = false,
    ) {}

    /**
     * @return Collection<int, Model>
     */
    abstract public function options(): Collection;

    public function selectedValue(): int|string|null
    {
        $value = old($this->name);

        return is_string($value) ? $value : $this->selected;
    }

    public function label(Model $option): string
    {
        $label = $option->getAttribute('display_name_fa') ?: $option->getAttribute('name_fa');

        return is_string($label) && $label !== '' ? $label : (string) $option->getKey();
    }

    public function code(Model $option): ?string
    {
        $code = $option->getAttribute('code');

        return is_string($code) ? $code : null;
    }

    protected function componentView(string $view): View
    {
        return app(ViewFactory::class)->make('iran-locations::components.'.$view);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    protected function optionRecords(string $modelKey, array $filters = []): Collection
    {
        $model = $this->newModel($modelKey);
        $query = $model->newQuery();
        $filters = $this->filledFilters($filters);
        $filters['status'] = 'active';

        if ($query instanceof LocationBuilder) {
            $query->filter($filters)->ordered();
        } else {
            $this->applyFallbackActive($query, $model);
            $this->applyFallbackOrder($query, $model);
        }

        return $query->get();
    }

    protected function newModel(string $key): Model
    {
        /** @var class-string<Model> $class */
        $class = LocationModelResolver::model($key);

        return new $class;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filledFilters(array $filters): array
    {
        return array_filter(
            $filters,
            static fn (mixed $value): bool => ! blank($value),
        );
    }

    private function applyFallbackActive(Builder $query, Model $model): void
    {
        if ($this->hasColumn($model, 'is_active')) {
            $query->where($model->qualifyColumn('is_active'), true);
        }

        if ($this->hasColumn($model, 'deprecated_at')) {
            $query->whereNull($model->qualifyColumn('deprecated_at'));
        }
    }

    private function applyFallbackOrder(Builder $query, Model $model): void
    {
        $column = $this->hasColumn($model, 'normalized_name') ? 'normalized_name' : 'name_fa';

        if ($this->hasColumn($model, $column)) {
            $query->orderBy($model->qualifyColumn($column));
        }

        $query->orderBy($model->getQualifiedKeyName());
    }

    private function hasColumn(Model $model, string $column): bool
    {
        return Schema::hasColumn($model->getTable(), $column);
    }
}
