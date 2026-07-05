<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\View\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Support\LocationRecord;

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
     * @return Collection<int, LocationRecord>
     */
    abstract public function options(): Collection;

    public function selectedValue(): int|string|null
    {
        $value = old($this->name);

        return is_string($value) ? $value : $this->selected;
    }

    public function label(LocationRecord $option): string
    {
        return $option->label();
    }

    public function code(LocationRecord $option): ?string
    {
        $code = $option->code();

        return $code !== '' ? $code : null;
    }

    protected function componentView(string $view): View
    {
        return app(ViewFactory::class)->make('iran-locations::components.'.$view);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LocationRecord>
     */
    protected function optionRecords(string $modelKey, array $filters = []): Collection
    {
        return $this->readRepository()->all($modelKey, array_merge($this->filledFilters($filters), [
            'status' => 'active',
        ]));
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

    private function readRepository(): LocationReadRepository
    {
        return app(LocationReadRepository::class);
    }
}
