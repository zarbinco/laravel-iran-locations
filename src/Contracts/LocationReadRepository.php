<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Contracts;

use Illuminate\Support\Collection;
use Zarbin\IranLocations\Support\LocationRecord;

interface LocationReadRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LocationRecord>
     */
    public function all(string $type, array $filters = []): Collection;

    public function find(string $type, string $code): ?LocationRecord;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{value: string, label: string, code: string, name_fa: mixed}>
     */
    public function options(string $type, array $filters = [], ?int $limit = null): Collection;

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, LocationRecord>
     */
    public function search(string $term, array $types = [], ?int $limit = null): Collection;
}
