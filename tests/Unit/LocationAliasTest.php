<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use ReflectionMethod;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\LocationAlias;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\TestCase;

class LocationAliasTest extends TestCase
{
    public function test_location_models_have_aliases_relation(): void
    {
        $models = [
            'Province' => new Province,
            'City' => new City,
            'CityRegion' => new CityRegion,
            'CityArea' => new CityArea,
            'Neighborhood' => new Neighborhood,
        ];

        foreach ($models as $label => $model) {
            $relation = $model->aliases();

            self::assertInstanceOf(MorphMany::class, $relation, "{$label} aliases relation is not morphMany.");
            self::assertInstanceOf(LocationAlias::class, $relation->getRelated());
        }
    }

    public function test_location_alias_auto_normalizes_using_bound_contract(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $alias = new LocationAlias([
            'alias' => 'Tehran',
        ]);

        $this->fireSavingEvent($alias);

        self::assertSame('search:Tehran', $alias->getAttribute('normalized_alias'));
    }

    public function test_location_alias_does_not_normalize_when_alias_normalization_is_disabled(): void
    {
        config()->set('iran-locations.normalization.aliases', false);
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $alias = new LocationAlias([
            'alias' => 'Tehran',
            'normalized_alias' => '',
        ]);

        $this->fireSavingEvent($alias);

        self::assertSame('', $alias->getAttribute('normalized_alias'));
    }

    private function fireSavingEvent(LocationAlias $alias): void
    {
        $fireModelEvent = new ReflectionMethod($alias, 'fireModelEvent');
        $fireModelEvent->setAccessible(true);
        $fireModelEvent->invoke($alias, 'saving', false);
    }

    private function fakeNormalizer(): LocationNormalizer
    {
        return new class implements LocationNormalizer
        {
            public function display(string $value): string
            {
                return 'display:'.$value;
            }

            public function search(string $value): string
            {
                return 'search:'.$value;
            }

            public function slug(string $value): string
            {
                return 'slug:'.$value;
            }
        };
    }
}
