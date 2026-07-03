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

## Intentionally Not Implemented Yet

- Full data sync engine, import sources, diffing, data checksums, safe update planning, and deprecation workflow.
- Admin CRUD UI, controllers, forms, policies, and Blade screens.
- Public API resources, controllers, request validation, and response filtering.
- Advanced query filters, dedicated builders, validation rules, and search ranking.
- Database record mutation inside placeholder sync/normalize commands.
- Alias search ranking and alias-aware query filters.

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
