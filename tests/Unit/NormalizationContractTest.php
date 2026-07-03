<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Unit;

use ReflectionMethod;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\IranLocationsManager;
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

        $province = new Province([
            'name_fa' => 'Tehran',
        ]);

        $fireModelEvent = new ReflectionMethod($province, 'fireModelEvent');
        $fireModelEvent->setAccessible(true);
        $fireModelEvent->invoke($province, 'saving', false);

        self::assertSame('search:Tehran', $province->getAttribute('normalized_name'));
        self::assertSame('slug:Tehran', $province->getAttribute('slug'));
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
