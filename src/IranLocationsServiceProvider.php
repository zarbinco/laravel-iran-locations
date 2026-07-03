<?php

declare(strict_types=1);

namespace Zarbin\IranLocations;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Zarbin\IranLocations\Commands\DoctorCommand;
use Zarbin\IranLocations\Commands\InstallCommand;
use Zarbin\IranLocations\Commands\NormalizeCommand;
use Zarbin\IranLocations\Commands\StatusCommand;
use Zarbin\IranLocations\Commands\SyncCommand;
use Zarbin\IranLocations\Contracts\LocationDataRepository;
use Zarbin\IranLocations\Contracts\LocationNormalizer;
use Zarbin\IranLocations\Data\JsonLocationDataRepository;
use Zarbin\IranLocations\Data\LocationDataValidator;
use Zarbin\IranLocations\Support\LocationDatabaseInspector;
use Zarbin\IranLocations\Support\PersianCoreLocationNormalizer;
use Zarbin\IranLocations\Sync\LocationSyncService;

class IranLocationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/iran-locations.php'), 'iran-locations');

        $this->app->singleton(LocationNormalizer::class, PersianCoreLocationNormalizer::class);
        $this->app->singleton(JsonLocationDataRepository::class);
        $this->app->singleton(LocationDataRepository::class, JsonLocationDataRepository::class);
        $this->app->singleton(LocationDataValidator::class);
        $this->app->singleton(LocationDatabaseInspector::class);
        $this->app->singleton(LocationSyncService::class);

        $this->app->singleton(IranLocationsManager::class, function ($app): IranLocationsManager {
            return new IranLocationsManager(
                $app->make(LocationNormalizer::class),
                $app->make(LocationDataRepository::class),
            );
        });

        $this->app->alias(IranLocationsManager::class, 'iran-locations');
    }

    public function boot(): void
    {
        $viewsPath = $this->packagePath('resources/views');
        $migrationsPath = $this->packagePath('database/migrations');

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'iran-locations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packagePath('config/iran-locations.php') => config_path('iran-locations.php'),
            ], 'iran-locations-config');

            if (is_dir($migrationsPath)) {
                $this->publishes([
                    $migrationsPath => database_path('migrations'),
                ], 'iran-locations-migrations');
            }

            if (is_dir($viewsPath)) {
                $this->publishes([
                    $viewsPath => resource_path('views/vendor/iran-locations'),
                ], 'iran-locations-views');
            }

            $this->commands([
                InstallCommand::class,
                StatusCommand::class,
                SyncCommand::class,
                DoctorCommand::class,
                NormalizeCommand::class,
            ]);
        }

        $this->loadConfiguredRoutes();
    }

    private function loadConfiguredRoutes(): void
    {
        if ((bool) config('iran-locations.admin.enabled', false)) {
            Route::middleware((array) config('iran-locations.admin.middleware', ['web', 'auth']))
                ->prefix((string) config('iran-locations.admin.prefix', 'admin/iran-locations'))
                ->group($this->packagePath('routes/admin.php'));
        }

        if ((bool) config('iran-locations.api.enabled', false)) {
            Route::middleware((array) config('iran-locations.api.middleware', ['web']))
                ->prefix((string) config('iran-locations.api.prefix', 'iran-locations/api'))
                ->group($this->packagePath('routes/api.php'));
        }
    }

    private function packagePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__);

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.$path;
    }
}
