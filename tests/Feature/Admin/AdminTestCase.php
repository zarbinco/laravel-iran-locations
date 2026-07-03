<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Zarbin\IranLocations\Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('iran-locations.admin.enabled', true);
        $app['config']->set('iran-locations.admin.middleware', ['web']);
        $app['config']->set('iran-locations.admin.prefix', 'admin/iran-locations');
        $app['config']->set('iran-locations.admin.gate', null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }
}
