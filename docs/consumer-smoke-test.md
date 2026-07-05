# Consumer Smoke Test

Use this checklist before tagging a release or when validating the package in a real Laravel application.

## Git Bash Fresh App Flow

Create a new Laravel application outside this package repository. Replace `/absolute/path/to/laravel-iran-locations` with your local checkout path:

```bash
mkdir -p /tmp/iran-locations-smoke
cd /tmp/iran-locations-smoke

composer create-project laravel/laravel app --prefer-dist
cd app

composer config repositories.iran-locations path /absolute/path/to/laravel-iran-locations
composer require zarbinco/laravel-iran-locations:@dev --with-all-dependencies
```

For a published release later, install normally instead:

```bash
composer require zarbinco/laravel-iran-locations
```

Laravel 11, 12, or 13 may be used when matching the supported dependency matrix. Keep `zarbinco/laravel-persian-core` on a stable semver-compatible version; do not change this package to depend on a development branch for release testing.

## Database

SQLite is enough for the smoke test:

```bash
php artisan key:generate
touch database/database.sqlite
php artisan vendor:publish --tag=iran-locations-config
php artisan vendor:publish --tag=iran-locations-migrations
php artisan vendor:publish --tag=iran-locations-views
php artisan migrate
```

## Commands

Run the commands before and after sync:

```bash
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
    'middleware' => ['api'],
],
```

Confirm route registration:

```bash
php artisan route:list --path=iran-locations
```

Open the admin dashboard, data page, and each location index/create/edit screen. Check API list, search, nested, and option endpoints return JSON responses.

For command-line JSON checks, start the Laravel server in one terminal and call a few endpoints from another:

```bash
php artisan serve --host=127.0.0.1 --port=8017
curl -s http://127.0.0.1:8017/iran-locations/api/status
curl -s "http://127.0.0.1:8017/iran-locations/api/provinces?per_page=5"
curl -s "http://127.0.0.1:8017/iran-locations/api/search?q=تهران"
```

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
composer run-script test:ci
composer run-script release:check
bash tools/release-check.sh
```

If a script is not defined or not configured in a given checkout, skip it or replace it with the package's configured equivalent and record the reason in the release notes.

## Archive Hygiene

From the package repository, build and inspect a Composer archive before publishing:

```bash
composer archive --format=zip --dir=build/release-check --file=laravel-iran-locations-release-check
php tools/check-archive.php build/release-check/laravel-iran-locations-release-check.zip

unzip -l build/release-check/laravel-iran-locations-release-check.zip | grep -E "(REVIEW_NOTES|\.phpunit\.cache|vendor/|node_modules/|_source/|coverage/|artifacts/|_review/)" \
  && echo "Unexpected private artifact found" \
  || echo "Archive hygiene looks clean"
```

Also confirm the archive is not a full project snapshot and does not contain nested zip, tar, build, release, or temporary review artifacts.
Archive hygiene tooling requires PHP `zip` support in the package development checkout. If Composer installation or `tools/check-archive.php` reports missing `ext-zip`, install or enable the PHP zip extension for that development environment. Consumer applications do not need `ext-zip` for normal package runtime use.

## Optional Smoke Report

Capture a small smoke report in the temporary app if you want an artifact for release review:

```bash
{
  php artisan about
  php artisan iran-locations:status
  php artisan route:list --path=iran-locations
} > iran-locations-smoke-report.txt

zip iran-locations-smoke-report.zip iran-locations-smoke-report.txt
```

Do not commit the temporary Laravel app or smoke report artifacts into this package repository.

## Cleanup

After recording the result, remove the temporary app if it is no longer needed:

```bash
cd /tmp
rm -rf iran-locations-smoke
```
