# Sync

The sync engine imports packaged JSON data into your application database through configured models and table names.

Default sync order follows data dependencies: provinces, counties, official districts, rural districts, cities, municipal regions and areas, neighborhoods, neighborhood-region mappings, and aliases.

## Dry Run

Always inspect a sync first:

```bash
php artisan iran-locations:sync --dry-run
```

Dry-run mode validates package data, resolves dependencies, reports creates, updates, skips, and deprecations, and does not write database records.

## Apply Sync

```bash
php artisan iran-locations:sync
```

Successful syncs write a data-version record with the package data version, checksum, and summary.

## Safety Behavior

- Tables are never truncated.
- Package-owned records are matched by stable `code`.
- Custom records are preserved.
- Missing package-owned records are deprecated by default.
- Hard delete behavior is rejected by the sync service.
- Empty non-authoritative datasets are not used to deprecate records during default full sync.

## Useful Commands

```bash
php artisan iran-locations:status
php artisan iran-locations:doctor
php artisan iran-locations:install
php artisan iran-locations:install --sync
```

`install` is non-mutating by default. Use `--sync` only when you want installation to apply package data after publishing assets.

## Options

Use `--dataset` to sync selected datasets. Use `--no-deprecate` to preserve missing package records during a sync. Use `--force` only when you intentionally want package sync to manage records with an unexpected source.
