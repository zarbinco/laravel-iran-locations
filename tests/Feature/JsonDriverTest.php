<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use ReflectionMethod;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationReadRepository;
use Zarbin\IranLocations\Facades\IranLocations;
use Zarbin\IranLocations\Repositories\JsonLocationReadRepository;
use Zarbin\IranLocations\Support\LocationRecord;
use Zarbin\IranLocations\Tests\TestCase;

class JsonDriverTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('iran-locations.storage.driver', 'json');
        $app['config']->set('iran-locations.admin.enabled', true);
        $app['config']->set('iran-locations.api.enabled', true);
        $app['config']->set('iran-locations.api.middleware', ['api']);
        $app['config']->set('iran-locations.api.prefix', 'iran-locations/api');
    }

    public function test_json_repository_reads_packaged_data_without_migrations(): void
    {
        $repository = $this->app->make(LocationReadRepository::class);

        self::assertInstanceOf(JsonLocationReadRepository::class, $repository);
        self::assertSame(31, $repository->all('provinces')->count());

        $city = $repository->find('city', 's.01.01.01.01');

        self::assertInstanceOf(LocationRecord::class, $city);
        self::assertSame('تهران', $city->nameFa());
        self::assertSame('s.01.01.01.01', IranLocations::find('city', 's.01.01.01.01')?->code());

        self::assertTrue($repository->all('cities', ['province_code' => 'p.01'])->contains(
            fn (LocationRecord $record): bool => $record->code() === 's.01.01.01.01',
        ));
        self::assertSame(22, $repository->all('city_regions', ['city_code' => 's.01.01.01.01'])->count());
        self::assertGreaterThan(0, $repository->all('neighborhoods', ['city_region_code' => 'r.01.01.01.01.05'])->count());

        $options = $repository->options('cities', ['q' => 'تهران'], 1);

        self::assertSame('s.01.01.01.01', $options[0]['value']);
        self::assertSame('s.01.01.01.01', $options[0]['code']);
        self::assertSame('تهران', $options[0]['label']);

        self::assertSame(
            's.01.01.01.01',
            $repository->search('تهران', ['cities'], 1)->first()?->code(),
        );
    }

    public function test_json_repository_id_filters_return_empty_results(): void
    {
        $repository = $this->app->make(LocationReadRepository::class);

        self::assertSame(0, $repository->all('cities', ['province_id' => 1])->count());
        self::assertSame(0, $repository->options('cities', ['province_id' => 1])->count());
    }

    public function test_json_cache_is_disabled_by_default_and_cache_key_includes_checksum(): void
    {
        $repository = $this->app->make(LocationReadRepository::class);

        self::assertFalse(config('iran-locations.storage.json.cache'));
        self::assertInstanceOf(JsonLocationReadRepository::class, $repository);

        $method = new ReflectionMethod(JsonLocationReadRepository::class, 'cacheKey');
        $key = $method->invoke($repository, 'cities');
        $manifest = $this->app->make(LocationDataRepository::class)->manifest();

        self::assertIsString($key);
        self::assertIsString($manifest['checksum'] ?? null);
        self::assertStringContainsString('0.2.0-dev', $key);
        self::assertStringContainsString((string) $manifest['checksum'], $key);
        self::assertStringEndsWith('.cities', $key);
    }

    public function test_json_driver_commands_are_read_only_and_do_not_need_tables(): void
    {
        self::assertSame(0, Artisan::call('iran-locations:doctor'));
        $doctorOutput = Artisan::output();
        self::assertStringContainsString('Driver: json', $doctorOutput);
        self::assertStringContainsString('Mode: read-only packaged JSON', $doctorOutput);
        self::assertStringContainsString('Database tables: skipped', $doctorOutput);
        self::assertStringContainsString('Packaged data: OK', $doctorOutput);

        self::assertSame(0, Artisan::call('iran-locations:status'));
        $statusOutput = Artisan::output();
        self::assertStringContainsString('Driver: json', $statusOutput);
        self::assertStringContainsString('Mode: read-only', $statusOutput);
        self::assertStringContainsString('Migration/sync required: no', $statusOutput);

        self::assertSame(1, Artisan::call('iran-locations:sync'));
        $syncOutput = Artisan::output();
        self::assertStringContainsString('The iran-locations:sync command is only available in database driver mode.', $syncOutput);
        self::assertStringContainsString('Current driver: json.', $syncOutput);
        self::assertStringContainsString('No sync is required because packaged JSON data is used directly.', $syncOutput);
    }

    public function test_json_driver_blade_select_uses_codes_without_tables(): void
    {
        $html = Blade::render(
            '<x-iran-locations::city-select name="city_code" province-code="p.01" selected="s.01.01.01.01" />',
        );

        self::assertStringContainsString('name="city_code"', $html);
        self::assertStringContainsString('value="s.01.01.01.01"', $html);
        self::assertMatchesRegularExpression('/value="s\.01\.01\.01\.01"[^>]*selected/', $html);
        self::assertStringContainsString('data-code="s.01.01.01.01"', $html);
    }

    public function test_json_driver_blade_select_with_only_id_parent_does_not_render_all_options(): void
    {
        $html = Blade::render(
            '<x-iran-locations::city-select name="city_code" :province-id="$provinceId" />',
            ['provinceId' => 1],
        );

        self::assertStringContainsString('name="city_code"', $html);
        self::assertStringNotContainsString('value="s.01.01.01.01"', $html);
    }

    public function test_json_driver_api_read_endpoints_work_without_tables(): void
    {
        $this->getJson('/iran-locations/api/status')
            ->assertOk()
            ->assertJsonPath('driver', 'json')
            ->assertJsonPath('database.tables', 'skipped')
            ->assertJsonPath('database.sync_required', false);

        $this->getJson('/iran-locations/api/options/cities?q='.urlencode('تهران').'&limit=1')
            ->assertOk()
            ->assertJsonPath('0.value', 's.01.01.01.01')
            ->assertJsonPath('0.code', 's.01.01.01.01');

        $this->getJson('/iran-locations/api/search?q='.urlencode('تهران').'&limit=1')
            ->assertOk()
            ->assertJsonPath('results.cities.0.code', 's.01.01.01.01');

        $this->getJson('/iran-locations/api/cities?province_code=p.01&q='.urlencode('تهران'))
            ->assertOk()
            ->assertJsonPath('data.0.code', 's.01.01.01.01')
            ->assertJsonMissing(['code' => 's.02.01.01.01']);

        $this->getJson('/iran-locations/api/cities/s.01.01.01.01/regions')
            ->assertOk()
            ->assertJsonFragment(['code' => 'r.01.01.01.01.05']);
    }

    public function test_json_driver_api_rejects_database_id_filters(): void
    {
        $this->getJson('/iran-locations/api/cities?province_id=1')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Database ID filters are not supported in JSON driver mode. Use code filters instead.')
            ->assertJsonPath('errors.province_id.0', 'The province_id filter is not supported in JSON driver mode. Use province_code instead.');

        $this->getJson('/iran-locations/api/options/cities?province_id=1')
            ->assertStatus(422)
            ->assertJsonPath('errors.province_id.0', 'The province_id filter is not supported in JSON driver mode. Use province_code instead.');

        $this->getJson('/iran-locations/api/cities/s.01.01.01.01/regions?city_id=1')
            ->assertStatus(422)
            ->assertJsonPath('errors.city_id.0', 'The city_id filter is not supported in JSON driver mode. Use city_code instead.');
    }

    public function test_admin_routes_are_not_registered_in_json_driver(): void
    {
        $this->get('/admin/iran-locations')->assertNotFound();
    }
}
