# Laravel Iran Locations

Laravel Iran Locations provides Iran location data, Eloquent models, safe database sync, query helpers, optional admin screens, optional read-only API endpoints, and plain Blade select components for Laravel applications.

> Pre-release note: this package is currently private and unreleased. The packaged dataset is versioned separately from any future package tag; the current data version is `0.2.0-dev`. Some supported dataset surfaces may be incomplete in this version, so a public/stable release should wait until the release checklist and consumer smoke tests pass.

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

The sync engine never truncates package tables. Custom records are preserved. Custom aliases and neighborhood-region mappings are preserved too. Package-owned rows missing from the current package data are deprecated by default instead of being hard deleted.
Package data is required to contain normalized/searchable fields. Sync writes those normalized fields and fills missing normalized or slug fields through the configured `LocationNormalizer`; sync normalization is not user-toggleable.
Use `--chunk` to process already-loaded package data records in smaller sync batches.

## Data Scope

The current packaged dataset includes:

- 31 provinces
- 484 counties
- 1087 official districts
- 73 rural districts
- 1456 cities
- 22 Tehran city regions
- 568 Tehran neighborhood or urban-place style records

The packaged data is generated from spreadsheet source files. The official hierarchy is province, county, official district, city, and rural district. The municipal hierarchy remains separate: city region, city area, and neighborhood. City areas and aliases are structurally supported but are empty in packaged data version `0.2.0-dev` unless your application adds records.

Public Persian display names in the packaged data are normalized from Arabic `ك/ي` to Persian `ک/ی`, and the 31 province capital city flags are populated. Data quality tests guard manifest counts, checksums, reference integrity, duplicate codes, public Persian text fields, documented duplicate neighborhood names, and province-capital mappings.

Treat this as versioned package data, not automatically complete, official, current national coverage. It does not currently include village, boundary, latitude/longitude, postal-code, routing, or always-current official gazette data unless explicitly documented. Verify source assumptions and licensing suitability before production, legal, regulatory, logistics, or high-stakes use.

## Alias Contract

Aliases store stable public location type keys instead of PHP class names: `province`, `county`, `official_district`, `rural_district`, `city`, `city_region`, `city_area`, and `neighborhood`. The package registers an Eloquent morph map for those keys and maps them to the configured model classes, so custom model configuration remains respected.

Admin and API inputs accept the supported stable keys and reject unsupported values such as arbitrary class names. Package data and sync may normalize plural dataset-style aliases, for example `cities` to `city`.

Stale package-owned aliases are deprecated by sync. Normal location search and the `activeAliases()` relationship consume only active, non-deprecated aliases, while `aliases()` remains the full relationship for admin or maintenance workflows. The read-only `/aliases` API defaults to active aliases and supports `status=active|inactive|deprecated|all`.

## Normalization

The package delegates Persian display, search, slug, and alias normalization to `zarbinco/laravel-persian-core` through the `LocationNormalizer` contract. It does not duplicate Persian character replacement or digit normalization logic locally.

## Eloquent Usage

```php
use Zarbin\IranLocations\Models\City;
use Zarbin\IranLocations\Models\CityRegion;
use Zarbin\IranLocations\Models\Neighborhood;
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

Official and municipal hierarchy helpers can be combined naturally:

```php
$regions = CityRegion::query()
    ->forCityCode('ir.city.001.001.001.001')
    ->orderedByNumber()
    ->get();

$regionNeighborhoods = Neighborhood::query()
    ->forRegionCode('ir.city.tehran.region.05')
    ->ordered()
    ->get();

$countyNeighborhoods = Neighborhood::query()
    ->forCountyCode('ir.county.001.001')
    ->ordered()
    ->get();
```

Normal `Neighborhood::regions()` and `CityRegion::neighborhoods()` relationships return active, non-deprecated mappings. Use `allRegions()` or `allNeighborhoods()` when maintenance code needs to inspect inactive or deprecated mapping rows. Admin record visibility continues to follow the package admin routes and settings.

## Admin UI

The admin UI is disabled by default. Enable it in `config/iran-locations.php`:

```php
'admin' => [
    'enabled' => true,
],
```

Configure the prefix, middleware, and optional gate in the same config file.
Keep admin routes behind application auth middleware. When `admin.gate` is configured, every package admin route enforces that gate consistently.
By default, admin users can create and maintain `source = custom` records, but direct edits or delete/deprecate actions for `source = package` records are blocked. Set `data.allow_package_record_direct_edit` to `true` only when you intentionally need to override package-owned records during private testing or release preparation.
Admin mutation forms validate parent hierarchy consistency, and alias forms accept only stable location type keys whose target records exist.
Public/stable release should wait until the release checklist and consumer smoke tests pass.

## API

The API is disabled by default. Enable it in config:

```php
'api' => [
    'enabled' => true,
],
```

Default endpoints are mounted under `iran-locations/api` and include status, search, list, nested list, alias, and option endpoints. The API is read-only.
The default API middleware stack is `['api']`; public applications should configure middleware deliberately, for example `['api', 'throttle:60,1']` or their own throttle/auth stack.
Nested API route parents resolve active, non-deprecated records by default. Conflicting nested parent filters, such as `/provinces/1/cities?province_id=2`, return `422` instead of being silently overridden.
HTTP request validation enforces `search.min_length` for API/admin search inputs that include `q`.

## Blade Components

The package registers components under the `iran-locations` namespace:

```blade
<x-iran-locations::province-select name="province_id" />
<x-iran-locations::city-select name="city_id" :province-id="$provinceId" />
<x-iran-locations::neighborhood-select name="neighborhood_id" :city-id="$cityId" />
```

Components are plain Blade, preserve old input, support parent filters, and require no JavaScript.

## Configuration

`config/iran-locations.php` controls table names, model classes, route keys, save-time normalization, package-record admin edit policy, admin routes, API routes, search, and pagination. Models and tables can be overridden for application-specific needs.

## Testing

```bash
composer test
composer run-script test:ci
composer run-script release:check
bash tools/release-check.sh
composer run-script format:test
composer analyse
composer validate --strict
```

`composer run-script release:check` runs the local release gate: Composer validation, tests, formatting, static analysis, Composer archive generation, and archive hygiene checks. `tools/release-check.sh` is a Git Bash convenience wrapper for the same gate. The CI workflow runs the supported PHP/Laravel/Testbench matrix and a separate archive hygiene gate, but it does not publish tags or releases.
The release/archive hygiene gate requires the PHP `zip` extension. It is declared as a development requirement only; consumer applications do not need `ext-zip` for normal runtime use. CI explicitly enables `zip` for test and release-gate jobs.

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
