# Laravel Iran Locations

Laravel Iran Locations provides Iran location data, Eloquent models, safe database sync, query helpers, optional admin screens, optional read-only API endpoints, and plain Blade select components for Laravel applications.

## Features

- Iran provinces, counties, official districts, rural districts, cities, and Tehran municipal records from a packaged dataset
- Versioned package data with manifest counts and checksums
- Persian text normalization through `zarbinco/laravel-persian-core`
- Safe database sync with dry-run support
- Eloquent models, relationships, configurable tables, and configurable model classes
- Query builders and validation-friendly filters
- Optional Blade/Tailwind admin UI
- Optional read-only HTTP API
- Plain Blade select components for forms
- Alias support for search-friendly location names

## Requirements

- PHP 8.2 or newer
- Laravel 11, 12, or 13
- `zarbinco/laravel-persian-core`

## Installation

```bash
composer require zarbinco/laravel-iran-locations
```

Publish the config, migrations, and views as needed:

```bash
php artisan vendor:publish --tag=iran-locations-config
php artisan vendor:publish --tag=iran-locations-migrations
php artisan vendor:publish --tag=iran-locations-views
php artisan migrate
```

## Syncing Data

Review changes first:

```bash
php artisan iran-locations:sync --dry-run
```

Apply the packaged data:

```bash
php artisan iran-locations:sync
php artisan iran-locations:status
php artisan iran-locations:doctor
```

The sync engine never truncates package tables. Custom records are preserved. Package-owned records missing from the current package data are deprecated by default instead of being hard deleted.

## Data Scope

The current packaged dataset includes:

- 31 provinces
- 484 counties
- 1087 official districts
- 73 rural districts
- 1456 cities
- 22 Tehran city regions
- 568 Tehran neighborhood or urban-place style records

The packaged data is generated from spreadsheet source files. The official hierarchy is province, county, official district, city, and rural district. The municipal hierarchy remains separate: city region, city area, and neighborhood. City areas and aliases are structurally supported but are empty in the packaged dataset unless your application adds records. Verify the packaged data and licensing suitability for your own use case.

## Normalization

The package delegates Persian display, search, slug, and alias normalization to `zarbinco/laravel-persian-core` through the `LocationNormalizer` contract. It does not duplicate Persian character replacement or digit normalization logic locally.

## Eloquent Usage

```php
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\Province;

$province = Province::query()->byCode('ir.province.001')->first();

$cities = City::query()
    ->forProvince($province)
    ->active()
    ->ordered()
    ->get();
```

Search and filters use the package builders:

```php
$cities = City::query()
    ->filter([
        'province_code' => 'ir.province.001',
        'q' => 'تهران',
        'status' => 'active',
        'sort' => 'name',
    ])
    ->paginate(25);
```

## Admin UI

The admin UI is disabled by default. Enable it in `config/iran-locations.php`:

```php
'admin' => [
    'enabled' => true,
],
```

Configure the prefix, middleware, and optional gate in the same config file.

## API

The API is disabled by default. Enable it in config:

```php
'api' => [
    'enabled' => true,
],
```

Default endpoints are mounted under `iran-locations/api` and include status, search, list, nested list, alias, and option endpoints. The API is read-only.

## Blade Components

The package registers components under the `iran-locations` namespace:

```blade
<x-iran-locations::province-select name="province_id" />
<x-iran-locations::city-select name="city_id" :province-id="$provinceId" />
<x-iran-locations::neighborhood-select name="neighborhood_id" :city-id="$cityId" />
```

Components are plain Blade, preserve old input, support parent filters, and require no JavaScript.

## Configuration

`config/iran-locations.php` controls table names, model classes, route keys, normalization, sync behavior, admin routes, API routes, search, and pagination. Models and tables can be overridden for application-specific needs.

## Testing

```bash
composer test
composer run-script format:test
composer analyse
composer validate --strict
```

## More Documentation

- [Data](docs/data.md)
- [Sync](docs/sync.md)
- [Admin UI](docs/admin.md)
- [API](docs/api.md)
- [Blade components](docs/components.md)
- [Extending](docs/extending.md)
- [Consumer smoke test](docs/consumer-smoke-test.md)
- [Release checklist](docs/release-checklist.md)
- [Changelog](CHANGELOG.md)

## License

The package is open-sourced software licensed under the [MIT license](LICENSE.md).
