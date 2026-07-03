# Review Notes

## Added

- Package composer metadata for `zarbinco/laravel-iran-locations` with Laravel auto-discovery.
- Configuration for tables, models, routing, normalization, data behavior, admin, API, search, and pagination.
- Service provider, facade, manager, location normalizer contract, and Persian Core normalizer adapter.
- Baseline Eloquent models and lightweight model concerns for configurable tables, lifecycle status, source, stable codes, display names, and save-time name normalization.
- Initial migrations for provinces, cities, city regions, city areas, neighborhoods, neighborhood-region assignments, aliases, and data versions.
- Safe placeholder Artisan commands for install, status, sync, doctor, and normalize workflows.
- Disabled-by-default admin/API route placeholders and a view placeholder directory.
- Minimal Orchestra Testbench suite covering provider/config boot, manager helpers, normalizer binding, migrations, model table config, contract-based normalization, and command registration.
- Shared alias relationship support for location models and save-time alias normalization through the `LocationNormalizer` contract.
- Versioned package data directory, JSON data repository, package data validator, SQL-to-JSON converter, and package data status/doctor command integration.

## Intentionally Not Implemented Yet

- Full data sync engine, import sources, diffing, data checksums, safe update planning, and deprecation workflow.
- Admin CRUD UI, controllers, forms, policies, and Blade screens.
- Public API resources, controllers, request validation, and response filtering.
- Validation rules and search ranking.
- Database record mutation inside placeholder sync/normalize commands.
- Alias search ranking.
- Database sync from the package JSON data files.
- Admin UI, API controllers, and HTTP request filter endpoints.

## How To Run Tests

```bash
composer install
composer test
vendor/bin/pint --test
composer analyse
```

## Dependency Assumptions

- `zarbinco/laravel-persian-core` is required with the committed stable constraint `^1.0`.
- During local verification, Packagist exposed `v0.1.0` and `dev-main` only. The tests were run by temporarily mapping the adjacent local checkout at `E:/laragon/www/laravel-persian-core` as version `1.0.0` in Composer's global config, then removing that override after verification.
- The package normalizer delegates Persian display/search normalization to Persian Core and does not duplicate Persian character replacement behavior locally.

## Suggested Next Step

Implement the first safe data lifecycle layer: source dataset contracts, data version detection, dry-run diff output, and non-destructive status/doctor checks before adding any write behavior.

## Data Conversion Notes

- The SQL source files `provinces.sql`, `cities.sql`, and `districts.sql` were provided at the project root and converted into package-owned JSON data files.
- The converter is available at `tools/convert-sql-data.php` and writes package JSON data from SQL inputs when those files are supplied.
- Province count: 31.
- City count: 1226.
- Neighborhood count: 505.
- Skipped rows: none.
- Missing foreign references: none.
- Names cleaned only by Persian Core: all generated `normalized_name` values were produced through the package normalizer/Persian Core adapter.
- `city_regions.json`, `city_areas.json`, and `aliases.json` are intentionally empty until verified source data exists.
- Regression tests now assert the fixed initial package data counts directly.
- phpMyAdmin-style dump parsing with comments, setup statements, directives, and multiple INSERT blocks is covered.
- No data semantics were changed while hardening these tests.
- Raw SQL source files remain excluded from package patch archives.

## Model And Relationship Hardening Notes

- Added resolver-backed configurable model and table lookup while preserving existing plural table keys for compatibility.
- Added self-referencing replacement relations for main location models.
- Added status mutator helpers for active, inactive, deprecated, and deprecation restore states.
- Hardened route key fallback to supported keys only: `id`, `code`, and `slug`.
- Updated save-time name normalization to preserve manually supplied non-empty slugs.
- Added relationship tests covering main model relations, aliases, replacement relations, configured model classes, configured table names, pivot table config, display-name fallback, status/source helpers, route keys, and fake normalizer usage.
- Database sync, admin UI, and API endpoints remain intentionally out of scope.
- Verification run: `composer test`, `vendor/bin/pint --test`, and `composer analyse` passed.
- Suggested next step: implement the first database sync dry-run layer before introducing any write behavior.

## Query Builder And Filter Notes

- Added custom Eloquent builders for provinces, cities, city regions, city areas, and neighborhoods.
- Common builder methods cover status, source, code, slug, search, default ordering, latest update ordering, safe array filters, and whitelisted sort keys.
- Province filters: `q`, `status`, `source`, `code`, `slug`, `has_cities`, and `sort`.
- City filters: `q`, `province_id`, `province_code`, `status`, `source`, `is_capital`, `has_regions`, `has_neighborhoods`, `code`, `slug`, and `sort`.
- City region filters: `q`, `city_id`, `city_code`, `number`, `type`, `status`, `source`, `code`, `slug`, and `sort`.
- City area filters: `q`, `city_id`, `city_code`, `region_id`, `region_code`, `number`, `status`, `source`, `code`, `slug`, and `sort`.
- Neighborhood filters: `q`, `province_id`, `province_code`, `city_id`, `city_code`, `region_id`, `region_code`, `area_id`, `area_code`, `type`, `status`, `source`, `has_region`, `missing_region`, `code`, `slug`, and `sort`.
- Search terms are normalized through the bound `LocationNormalizer` contract and grouped before matching `normalized_name`, `name_fa`, `slug`, `code`, and aliases when alias search is enabled.
- Sort handling is whitelist-only. Relationship-like sort keys use local foreign key columns for now; no fragile joins were added.
- Added lightweight filter helpers for blank string handling and common boolean-like values.
- Builder tests use local database records and a fake normalizer binding instead of generated package data.
- No generated JSON data, raw SQL sources, database sync behavior, admin UI, API endpoints, or Blade components were changed.
- Verification run: `composer test`, `vendor/bin/pint --test`, `composer analyse`, and `composer validate --strict` passed.
- Skipped tests or failures: none.
- Suggested next implementation step: add a non-destructive sync preview that reports differences without mutating user data.

## Safe Data Sync Notes

- Added a structured sync engine with options, per-record changes, per-dataset summaries, aggregate results, and clear sync exceptions.
- Sync order is dependency-safe: provinces, cities, city regions, city areas, neighborhoods, then aliases.
- Dry-run mode validates package data, resolves dependencies by stable codes, builds create/update/deprecation previews, and does not write location or data-version records.
- Real sync validates package data, upserts package-owned records by stable `code`, records a data-version entry on successful runs, and never truncates tables.
- Package records missing from non-empty synced package datasets are deprecated by default. Empty package datasets are not deprecated during default full sync; explicit dataset sync can deprecate them when configured.
- Custom records are preserved, including same-code conflicts and unrelated custom records. Records with unknown source are skipped unless force mode is used.
- Package updates preserve existing non-empty `display_name_fa` overrides, restore records that reappear in package data, and keep normalization delegated to the existing `LocationNormalizer` contract.
- Hard delete behavior is intentionally rejected with a clear exception; only `deprecate` and `ignore` are supported safely.
- Added a database inspector for table presence, configured model checks, safe dataset counts, and latest applied data-version reporting.
- Updated `iran-locations:sync` with dry-run, dataset selection, force, no-deprecate, and summary output.
- Updated `iran-locations:status` to show package metadata, safe database counts, latest applied data version, and whether the database appears synced.
- Updated `iran-locations:doctor` to report package data validation, configured model status, table presence, and latest applied version without modifying data.
- Updated `iran-locations:install` so it remains non-mutating by default and runs sync only when `--sync` is provided.
- Added service and command tests for dry-run, full sync counts, idempotency, relationships, custom preservation, deprecation safety, empty dataset behavior, dependency failures, hard-delete rejection, status, doctor, and install behavior.
- No generated JSON data, raw SQL sources, admin UI, API endpoints, Blade components, release docs, Tehran municipal regions, official divisions, or geo/boundary data were changed.
- Verification run: `composer test`, `vendor/bin/pint --test`, `composer analyse`, and `composer validate --strict` passed.
- Skipped tests or failures: none.
- Suggested next implementation step: add release-facing documentation and a small application-level sync preview UI after the package API stabilizes.

## Status Count Detection Notes

- Status sync detection now uses active package-owned counts instead of raw database table counts.
- Custom records no longer make status look unsynced when package-owned active counts still match package data.
- Deprecated package records no longer make status look unsynced when active package-owned counts still match package data.
- Non-authoritative empty datasets are ignored for synced detection when the manifest marks `contains.*` as false.
- Status output still shows raw database counts and now also shows package active counts for debugging.
- Added command tests covering normal sync, custom records, deprecated package records, local records in non-authoritative datasets, wrong active package counts, and missing applied data versions.
- Verification run: `composer test`, `vendor/bin/pint --test`, `composer analyse`, and `composer validate --strict` passed.
- Skipped tests or failures: none.

## Admin Blade UI Notes

- Added an optional admin UI that loads only when `iran-locations.admin.enabled` is true and uses the configured prefix, middleware, and optional gate.
- Added admin routes for dashboard, data status, data sync, provinces, cities, city regions, city areas, neighborhoods, and aliases under `iran-locations.admin.*` route names.
- Added plain Laravel controllers, FormRequests, and publishable Blade views under the `iran-locations` view namespace.
- Index screens use GET filters, Laravel pagination with query strings, and the existing query builder/filter layer where available.
- Create and edit screens use normal Blade forms and rely on model save-time normalization through the bound `LocationNormalizer` contract.
- Admin-created location and alias records default to `custom` unless a source is explicitly provided.
- Destroy actions do not hard delete package-owned records; package-owned lifecycle records are deprecated, while custom records are deleted when safe or deactivated if related records block deletion.
- Added a data status page with package counts, raw database counts, active package-owned counts, latest applied version, dry-run sync, apply sync, and sync summary output.
- Added admin authorization tests covering null gate, allowed gate, and denied gate behavior.
- Added admin route, dashboard, data sync, CRUD, validation, relationship, and normalization coverage.
- Public API endpoints, Blade select components for application forms, release docs, frontend dependencies, JavaScript frameworks, generated data changes, and public README changes remain intentionally out of scope.
- Verification run: `composer test`, `vendor/bin/pint --test`, `composer analyse`, and `composer validate --strict` passed.
- Skipped tests or failures: none.
- Suggested next implementation step: perform a final release audit and add end-user documentation without process wording.

## Admin Option And Alias Search Notes

- Relation option lists no longer have an arbitrary 500-record limit.
- Relation option lists now use a shared admin helper that prefers active, non-deprecated records and ordered package builders.
- Custom configured models without the package builder fall back to simple active/deprecated filters when those columns exist.
- Alias index search now keeps raw alias matching and normalizes the query through the bound `LocationNormalizer` contract before checking `normalized_alias`.
- Added regression tests for option lists beyond 500 records, inactive/deprecated option exclusion, and normalized alias searching.
- Verification run: `composer test`, `vendor/bin/pint --test`, `composer analyse`, and `composer validate --strict` passed.
- Skipped tests or failures: none.
