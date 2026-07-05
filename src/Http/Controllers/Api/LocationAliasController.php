<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

    public function index(AliasApiRequest $request): AnonymousResourceCollection|JsonResponse
    {
        if ($this->usesJsonReadRepository()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'driver' => 'json',
                    'mode' => 'read-only packaged JSON',
                ],
            ]);
        }

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

        $this->applyStatus($query, (string) ($filters['status'] ?? 'active'));

        $sort = (string) ($filters['sort'] ?? '');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, ['alias', 'source', 'created_at', 'updated_at'], true)) {
            $column = 'alias';
        }

        $query->orderBy($column, $direction)->orderBy('id');
    }

    private function applyStatus(Builder $query, string $status): void
    {
        match ($status) {
            'inactive' => $query->where('is_active', false),
            'deprecated' => $query->whereNotNull('deprecated_at'),
            'all' => $query,
            default => $query->where('is_active', true)->whereNull('deprecated_at'),
        };
    }
}
