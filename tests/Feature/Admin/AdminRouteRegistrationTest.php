<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Admin;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Zarbin\IranLocations\Tests\TestCase;

class AdminRouteRegistrationTest extends TestCase
{
    public function test_admin_routes_are_not_registered_when_disabled(): void
    {
        self::assertFalse(Route::has('iran-locations.admin.dashboard'));
    }
}

class EnabledAdminRouteRegistrationTest extends AdminTestCase
{
    public function test_admin_routes_are_registered_when_enabled(): void
    {
        self::assertTrue(Route::has('iran-locations.admin.dashboard'));
        self::assertTrue(Route::has('iran-locations.admin.provinces.index'));
    }

    public function test_default_admin_prefix_works(): void
    {
        $this->get('/admin/iran-locations')->assertOk();
    }
}

class CustomPrefixAdminRouteRegistrationTest extends TestCase
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
        $app['config']->set('iran-locations.admin.prefix', 'control-panel/locations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(dirname(__DIR__, 3).'/database/migrations');
        $this->artisan('migrate')->run();
    }

    public function test_configured_prefix_works(): void
    {
        $this->get('/control-panel/locations')->assertOk();
    }
}
