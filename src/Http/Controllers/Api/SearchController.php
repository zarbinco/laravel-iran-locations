<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Zarbin\IranLocations\Builders\LocationBuilder;
use Zarbin\IranLocations\Http\Controllers\Api\Concerns\ResolvesLocationApiModels;
use Zarbin\IranLocations\Http\Requests\Api\SearchApiRequest;
use Zarbin\IranLocations\Http\Resources\CityAreaResource;
use Zarbin\IranLocations\Http\Resources\CityRegionResource;
use Zarbin\IranLocations\Http\Resources\CityResource;
use Zarbin\IranLocations\Http\Resources\CountyResource;
use Zarbin\IranLocations\Http\Resources\NeighborhoodResource;
use Zarbin\IranLocations\Http\Resources\OfficialDistrictResource;
use Zarbin\IranLocations\Http\Resources\ProvinceResource;
use Zarbin\IranLocations\Http\Resources\RuralDistrictResource;

class SearchController extends Controller
{
    use ResolvesLocationApiModels;

    public function __invoke(SearchApiRequest $request): JsonResponse
    {
        $query = (string) $request->validated('q');
        $limit = $request->limit();

        if ($this->usesJsonReadRepository()) {
            return response()->json([
                'query' => $query,
                'results' => $this->jsonResults($query, $limit),
            ]);
        }

        return response()->json([
            'query' => $query,
            'results' => [
                'provinces' => $this->resourceArray(ProvinceResource::class, $this->search('province', $query, $limit), $request),
                'counties' => $this->resourceArray(CountyResource::class, $this->search('county', $query, $limit, ['province']), $request),
                'official_districts' => $this->resourceArray(OfficialDistrictResource::class, $this->search('official_district', $query, $limit, ['province', 'county']), $request),
                'rural_districts' => $this->resourceArray(RuralDistrictResource::class, $this->search('rural_district', $query, $limit, ['province', 'county', 'officialDistrict']), $request),
                'cities' => $this->resourceArray(CityResource::class, $this->search('city', $query, $limit, ['province', 'county', 'officialDistrict']), $request),
                'city_regions' => $this->resourceArray(CityRegionResource::class, $this->search('city_region', $query, $limit, ['city']), $request),
                'city_areas' => $this->resourceArray(CityAreaResource::class, $this->search('city_area', $query, $limit, ['region.city']), $request),
                'neighborhoods' => $this->resourceArray(NeighborhoodResource::class, $this->search('neighborhood', $query, $limit, ['city', 'defaultRegion', 'defaultArea']), $request),
            ],
        ]);
    }

    /**
     * @param  array<int, string>  $with
     */
    private function search(string $key, string $term, int $limit, array $with = []): mixed
    {
        $query = $this->query($key)->with($with);

        if ($query instanceof LocationBuilder) {
            $query->active()->search($term)->ordered();
        } else {
            $this->applyLocationFilters($query, ['status' => 'active', 'q' => $term]);
        }

        return $query->limit($limit)->get();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function jsonResults(string $query, int $limit): array
    {
        $types = [
            'province' => 'provinces',
            'county' => 'counties',
            'official_district' => 'official_districts',
            'rural_district' => 'rural_districts',
            'city' => 'cities',
            'city_region' => 'city_regions',
            'city_area' => 'city_areas',
            'neighborhood' => 'neighborhoods',
        ];
        $results = [];

        foreach ($types as $type => $key) {
            $results[$key] = $this->recordArrayCollection(
                $this->readRepository()->all($type, ['q' => $query])->take($limit)->values(),
            );
        }

        return $results;
    }
}
