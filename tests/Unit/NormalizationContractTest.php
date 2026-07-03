<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\IranLocationsManager;
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityArea;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
use Zarbin\IranLocations\Models\Province;
use Zarbin\IranLocations\Tests\TestCase;

class NormalizationContractTest extends TestCase
{
    public function test_manager_uses_bound_normalizer_contract(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());
        $this->app->forgetInstance(IranLocationsManager::class);

        $manager = $this->app->make(IranLocationsManager::class);

        self::assertSame('search:Tehran', $manager->normalizeForSearch('Tehran'));
        self::assertSame('display:Tehran', $manager->normalizeForDisplay('Tehran'));
    }

    public function test_model_name_normalization_uses_bound_contract(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        foreach ($this->locationModels() as $model) {
            $this->fireSavingEvent($model);

            self::assertSame('search:Tehran', $model->getAttribute('normalized_name'));
            self::assertSame('slug:Tehran', $model->getAttribute('slug'));
        }
    }

    public function test_manually_supplied_slug_is_preserved(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $province = new Province([
            'name_fa' => 'Tehran',
            'slug' => 'manual-slug',
        ]);

        $this->fireSavingEvent($province);

        self::assertSame('search:Tehran', $province->getAttribute('normalized_name'));
        self::assertSame('manual-slug', $province->getAttribute('slug'));
    }

    public function test_location_name_normalization_can_be_disabled(): void
    {
        config()->set('iran-locations.normalization.on_save', false);
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $province = new Province([
            'name_fa' => 'Tehran',
        ]);

        $this->fireSavingEvent($province);

        self::assertNull($province->getAttribute('normalized_name'));
        self::assertNull($province->getAttribute('slug'));
    }

    public function test_blank_names_are_not_normalized(): void
    {
        $this->app->instance(LocationNormalizer::class, $this->fakeNormalizer());

        $province = new Province([
            'name_fa' => '',
        ]);

        $this->fireSavingEvent($province);

        self::assertNull($province->getAttribute('normalized_name'));
        self::assertNull($province->getAttribute('slug'));
    }

    /**
     * @return array<int, Model>
     */
    private function locationModels(): array
    {
        return [
            new Province(['name_fa' => 'Tehran']),
            new City(['name_fa' => 'Tehran']),
            new CityRegion(['name_fa' => 'Tehran']),
            new CityArea(['name_fa' => 'Tehran']),
            new Neighborhood(['name_fa' => 'Tehran']),
        ];
    }

    private function fireSavingEvent(Model $model): void
    {
        $fireModelEvent = new ReflectionMethod($model, 'fireModelEvent');
        $fireModelEvent->setAccessible(true);
        $fireModelEvent->invoke($model, 'saving', false);
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
