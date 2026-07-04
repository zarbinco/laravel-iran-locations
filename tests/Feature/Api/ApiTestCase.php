<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

use Illuminate\Foundation\Application;
use Zarbin\IranLocations\Tests\Support\CreatesLocationTestData;
use Zarbin\IranLocations\Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use CreatesLocationTestData;

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('iran-locations.api.enabled', true);
        $app['config']->set('iran-locations.api.middleware', ['web']);
        $app['config']->set('iran-locations.api.prefix', 'iran-locations/api');
        $app['config']->set('iran-locations.api.pagination.per_page', 2);
        $app['config']->set('iran-locations.api.pagination.max_per_page', 3);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindFakeLocationNormalizer();
        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }
}
