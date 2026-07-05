<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\AliasApiRequest;
use Zarbin\IranLocations\Http\Resources\LocationAliasResource;
use Zarbin\IranLocations\Support\LocationModelResolver;

class LocationAliasController extends Controller
{
    use ResolvesLocationApiModels;

    public function index(AliasApiRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = $this->query('location_alias');

        $this->applyFilters($query, $filters);

        return LocationAliasResource::collection($this->paginate($query, $request));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $raw = trim((string) $filters['q']);
            $normalized = app(LocationNormalizer::class)->search($raw);

            $query->where(function (Builder $query) use ($normalized, $raw): void {
                $query->where('alias', 'like', '%'.$raw.'%')
                    ->orWhere('normalized_alias', 'like', '%'.$normalized.'%');
            });
        }

        if (filled($filters['source'] ?? null) && $filters['source'] !== 'all') {
            $query->where('source', $filters['source']);
        }

        if (filled($filters['location_type'] ?? null)) {
            $query->where('location_type', LocationModelResolver::normalizeLocationType((string) $filters['location_type']));
        }

        $sort = (string) ($filters['sort'] ?? '');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, ['alias', 'source', 'created_at', 'updated_at'], true)) {
            $column = 'alias';
        }

        $query->orderBy($column, $direction)->orderBy('id');
    }
}
