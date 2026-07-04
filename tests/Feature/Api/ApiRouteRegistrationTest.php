<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature\Api;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Zarbin\IranLocations\Tests\TestCase;

class ApiRouteRegistrationTest extends TestCase
{
    public function test_api_routes_are_not_registered_when_disabled(): void
    {
        self::assertFalse(Route::has('iran-locations.api.status'));
        self::assertFalse(Route::has('iran-locations.api.provinces.index'));
    }
}

class EnabledApiRouteRegistrationTest extends ApiTestCase
{
    public function test_api_routes_are_registered_when_enabled(): void
    {
        self::assertTrue(Route::has('iran-locations.api.status'));
        self::assertTrue(Route::has('iran-locations.api.provinces.index'));
        self::assertTrue(Route::has('iran-locations.api.counties.index'));
        self::assertTrue(Route::has('iran-locations.api.official-districts.index'));
        self::assertTrue(Route::has('iran-locations.api.rural-districts.index'));
        self::assertTrue(Route::has('iran-locations.api.options.cities'));
        self::assertTrue(Route::has('iran-locations.api.options.counties'));
    }

    public function test_default_api_prefix_works(): void
    {
        $this->getJson('/iran-locations/api/status')->assertOk();
    }
}

class CustomPrefixApiRouteRegistrationTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('iran-locations.api.enabled', true);
        $app['config']->set('iran-locations.api.middleware', ['web']);
        $app['config']->set('iran-locations.api.prefix', 'geo/iran');
    }

    public function test_configured_api_prefix_works(): void
    {
        $this->getJson('/geo/iran/status')->assertOk();
    }
}

class MiddlewareApiRouteRegistrationTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('iran-locations.api.enabled', true);
        $app['config']->set('iran-locations.api.middleware', ['web', ApiMarkerMiddleware::class]);
        $app['config']->set('iran-locations.api.prefix', 'iran-locations/api');
    }

    public function test_configured_api_middleware_applies(): void
    {
        $this->getJson('/iran-locations/api/status')
            ->assertOk()
            ->assertHeader('X-Iran-Locations-Test', 'yes');
    }
}

class ApiMarkerMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $response->headers->set('X-Iran-Locations-Test', 'yes');

        return $response;
    }
}
