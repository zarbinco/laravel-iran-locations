# Consumer Smoke Test

Use this checklist before tagging a release or when validating the package in a real Laravel application.

## Fresh App

Create a new Laravel application outside this package repository:

```bash
composer create-project laravel/laravel:^12.0 consumer-app --prefer-dist
cd consumer-app
```

Laravel 11 or 13 may also be used when matching your supported dependency matrix.

## Local Package Install

For a published release, install normally:

```bash
composer require zarbinco/laravel-iran-locations
```

When testing local package checkouts before all related tags are available, add path repositories only in the consumer app:

```bash
composer config repositories.laravel-persian-core path ../laravel-persian-core
composer config repositories.laravel-iran-locations path ../laravel-iran-locations
composer require zarbinco/laravel-iran-locations:* --prefer-source
```

Keep `zarbinco/laravel-persian-core` on a stable semver-compatible version. Do not change this package to depend on a development branch for release testing.

## Database

SQLite is enough for the smoke test:

```bash
php artisan key:generate
php artisan vendor:publish --tag=iran-locations-config
php artisan vendor:publish --tag=iran-locations-migrations
php artisan vendor:publish --tag=iran-locations-views
php artisan migrate
```

## Commands

Run the commands before and after sync:

```bash
php artisan iran-locations:status
php artisan iran-locations:doctor
php artisan iran-locations:sync --dry-run
php artisan iran-locations:sync
php artisan iran-locations:status
php artisan iran-locations:doctor
```

Expected synced counts:

- Provinces: 31
- Counties: 484
- Official districts: 1087
- Rural districts: 73
- Cities: 1456
- City regions: 22
- City areas: 0
- Neighborhoods: 568
- Neighborhood-region mappings: 568
- Aliases: 0

## Admin And API

Enable the admin UI with `middleware` suitable for the test app:

```php
'admin' => [
    'enabled' => true,
    'middleware' => ['web'],
],
```

Enable the read-only API:

```php
'api' => [
    'enabled' => true,
    'middleware' => ['web'],
],
```

Confirm route registration:

```bash
php artisan route:list --path=iran-locations
```

Open the admin dashboard, data page, and each location index/create/edit screen. Check API list, search, nested, and option endpoints return JSON responses.

## Blade Components

Render the main select components in a temporary view or through `Blade::render()`:

```blade
<x-iran-locations::province-select name="province_id" />
<x-iran-locations::county-select name="county_id" />
<x-iran-locations::official-district-select name="official_district_id" />
<x-iran-locations::rural-district-select name="rural_district_id" />
<x-iran-locations::city-select name="city_id" />
<x-iran-locations::city-region-select name="city_region_id" />
<x-iran-locations::city-area-select name="city_area_id" />
<x-iran-locations::neighborhood-select name="neighborhood_id" />
```

Also check filtered variants for province, county, official district, city, and neighborhood parents. Components should render active, non-deprecated records by default and should preserve `selected`, `placeholder`, `class`, `required`, and `disabled` props.

## Package Checks

Run the package suite after any release-readiness fix:

```bash
composer validate --strict
composer test
composer run-script format:test
composer analyse
```
